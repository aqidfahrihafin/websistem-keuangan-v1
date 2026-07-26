<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Configurable Midtrans transaction-fee schedule per top-up channel, and
 * whether that fee is charged on top to the wali (surcharge) or absorbed by
 * the pondok as an operating expense. Real rates come from the admin's own
 * Midtrans merchant contract - everything defaults to zero/pondok-absorbed
 * so shipping this feature never silently changes pricing or accounting
 * until an admin actively configures it from Pengaturan > Midtrans.
 *
 * Stored as flat Setting rows (Setting::value is a scalar-string-only,
 * encrypted cast with no JSON helper - see AppSettingsService for the same
 * convention) rather than one JSON blob, built data-driven off
 * TopupWaliService::METODE_* so the channel list can't drift between here
 * and the actual supported channels.
 */
class MidtransFeeService
{
    public const TIPE_TETAP = 'tetap';

    public const TIPE_PERSEN = 'persen';

    /**
     * @deprecated Superseded by KEY_DIBEBANKAN_WALI_TOPUP/_TAGIHAN below,
     * which let the admin decide the fee-bearer separately per purpose
     * (mis. pondok menanggung biaya untuk tagihan, wali menanggung untuk
     * top up biasa). Kept only as a one-time migration fallback in
     * dibebankanWali() below, for an install that configured the single
     * old toggle before this split existed - never written to again by
     * save().
     */
    private const KEY_DIBEBANKAN_WALI_LEGACY = 'midtrans_biaya_dibebankan_wali';

    private const KEY_DIBEBANKAN_WALI_TOPUP = 'midtrans_biaya_dibebankan_wali_topup';

    private const KEY_DIBEBANKAN_WALI_TAGIHAN = 'midtrans_biaya_dibebankan_wali_tagihan';

    private const METODE = [
        TopupWaliService::METODE_BNI_VA,
        TopupWaliService::METODE_BCA_VA,
        TopupWaliService::METODE_BRI_VA,
        TopupWaliService::METODE_QRIS,
    ];

    /**
     * Whether the Midtrans fee is charged on top to the wali (surcharge) or
     * absorbed by the pondok, for the given purpose - a plain top-up
     * ($untukTagihan = false) and a direct-to-tagihan payment
     * ($untukTagihan = true) can be configured independently since v1.3
     * (mis. pondok selalu menanggung biaya untuk pembayaran tagihan, tapi
     * membebankan biaya top up biasa ke wali). Falls back to the single
     * legacy toggle (pre-split) if the purpose-specific key was never set,
     * so an install configured before this split existed keeps behaving the
     * same for both purposes until an admin re-saves the settings form.
     */
    public function dibebankanWali(bool $untukTagihan = false): bool
    {
        $key = $untukTagihan ? self::KEY_DIBEBANKAN_WALI_TAGIHAN : self::KEY_DIBEBANKAN_WALI_TOPUP;
        $value = Setting::get($key);

        if ($value === null) {
            $value = Setting::get(self::KEY_DIBEBANKAN_WALI_LEGACY);
        }

        return $value === '1';
    }

    public function tipe(string $metode): string
    {
        return Setting::get("midtrans_biaya_{$metode}_tipe") ?: self::TIPE_TETAP;
    }

    public function nilai(string $metode): float
    {
        return (float) (Setting::get("midtrans_biaya_{$metode}_nilai") ?? '0');
    }

    /**
     * The fee for this channel/nominal under the currently configured
     * schedule. A percentage fee legitimately rounding to 0 on a very small
     * nominal isn't a bug worth guarding against - Midtrans VA fees are
     * typically flat-Rupiah anyway, percentage is mostly QRIS/cards.
     */
    public function hitungBiaya(string $metode, int $nominal): int
    {
        return $this->tipe($metode) === self::TIPE_PERSEN
            ? (int) round($nominal * $this->nilai($metode) / 100)
            : (int) round($this->nilai($metode));
    }

    /** What Midtrans is actually charged for this channel/nominal under the current policy. */
    public function grossAmount(string $metode, int $nominal, bool $untukTagihan = false): int
    {
        return $this->dibebankanWali($untukTagihan) ? $nominal + $this->hitungBiaya($metode, $nominal) : $nominal;
    }

    /** @return array<string, array{tipe: string, nilai: float}> keyed by metode kode - feeds both the settings form and TopupController::pengaturan(). */
    public function semuaChannel(): array
    {
        return collect(self::METODE)
            ->mapWithKeys(fn (string $metode) => [$metode => [
                'tipe' => $this->tipe($metode),
                'nilai' => $this->nilai($metode),
            ]])
            ->all();
    }

    /** @param array<string, array{tipe: string, nilai: float|int|string}> $channel */
    public function save(bool $dibebankanWaliTopup, bool $dibebankanWaliTagihan, array $channel): void
    {
        Setting::put(self::KEY_DIBEBANKAN_WALI_TOPUP, $dibebankanWaliTopup ? '1' : '0');
        Setting::put(self::KEY_DIBEBANKAN_WALI_TAGIHAN, $dibebankanWaliTagihan ? '1' : '0');

        foreach (self::METODE as $metode) {
            Setting::put("midtrans_biaya_{$metode}_tipe", $channel[$metode]['tipe'] ?? self::TIPE_TETAP);
            Setting::put("midtrans_biaya_{$metode}_nilai", (string) ($channel[$metode]['nilai'] ?? 0));
        }
    }
}
