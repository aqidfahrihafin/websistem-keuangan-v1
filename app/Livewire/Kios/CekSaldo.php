<?php

namespace App\Livewire\Kios;

use App\Exceptions\InvalidTransaksiException;
use App\Models\Device;
use App\Models\KartuSantri;
use App\Models\PenarikanRequest;
use App\Models\Santri;
use App\Services\PenarikanService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Unauthenticated, walk-up kiosk screen: a santri taps their RFID card (the
 * card reader emulates a keyboard, typing the UID into $uid followed by
 * Enter) to see their saldo & sisa limit penarikan, then - for an in-policy
 * amount (sufficient saldo, inside the allowed jam, under the daily limit),
 * on a registered kiosk_penarikan device that's currently aktif - completes
 * the withdrawal right here: enter nominal, scan a fingerprint (the kiosk's
 * fingerprint reader is expected to type the matched reference into
 * $fingerprint_ref the same way the card reader types $uid), cash
 * dispensed. No pengurus step for this case - see
 * PenarikanService::ajukanMandiri() for why that's safe (fingerprint match
 * against the santri's own card is the authorization, same as
 * PenarikanService::fulfill() already required from a pengurus).
 *
 * Amounts that need surat keterangan (outside jam or over the daily limit),
 * and any visit through a device that isn't a kiosk_penarikan currently
 * aktif (including the bare /kios URL with no device at all), never reach
 * self-service here - the santri is told to log in and use the existing
 * Santri\Penarikan\Request upload+admin-review flow instead.
 */
#[Layout('layouts::kiosk')]
class CekSaldo extends Component
{
    private const MAKS_PERCOBAAN_FINGERPRINT = 3;

    public ?Device $device = null;

    public string $uid = '';

    public ?int $santriId = null;

    public ?int $nominal = null;

    public string $fingerprint_ref = '';

    public int $percobaanFingerprint = 0;

    public string $step = 'idle';

    /** @var array{nominal: int, saldo_setelah: int, waktu: string}|null */
    public ?array $hasil = null;

    public function mount(?Device $device = null): void
    {
        $this->device = $device;
    }

    /**
     * Auto-submits once the RFID reader finishes "typing" the UID, instead
     * of relying on the reader also sending a trailing Enter keystroke
     * (not every keyboard-wedge reader does). wire:model.live.debounce on
     * $uid in the view means this only fires ~300ms after typing pauses -
     * comfortably after a reader's whole burst of characters, never
     * mid-keystroke. The <form wire:submit="scan"> is still there too, so
     * a manually-typed UID + real Enter press still works the same way;
     * scan() resetting $uid to '' immediately makes a double-fire from
     * both paths harmless (the second call just sees an empty uid).
     */
    public function updatedUid(): void
    {
        if (trim($this->uid) !== '') {
            $this->scan();
        }
    }

    public function scan(): void
    {
        $uid = trim($this->uid);
        $this->uid = '';

        if ($uid === '') {
            return;
        }

        $rateLimitKey = 'kios-scan:'.request()->ip();

        if (RateLimiter::tooManyAttempts($rateLimitKey, 20)) {
            $this->step = 'rate_limited';

            return;
        }

        RateLimiter::hit($rateLimitKey, 60);

        // A real scan attempt at this specific machine - the "last seen"
        // signal the admin device screen shows, updated per customer
        // interaction rather than a separate heartbeat mechanism.
        $this->device?->update(['last_seen_at' => now()]);

        $kartu = KartuSantri::with('santri.lembaga')
            ->where('uid_kartu_hash', KartuSantri::hashReference($uid))
            ->where('status', KartuSantri::STATUS_AKTIF)
            ->first();

        if (! $kartu || ! $kartu->santri) {
            $this->step = 'not_found';

            return;
        }

        $this->santriId = $kartu->santri->id;
        $this->step = 'found';
    }

    /**
     * Only validates and moves to the fingerprint step - nothing is
     * persisted yet, so an abandoned attempt (santri walks away before
     * scanning) never leaves a stray PenarikanRequest row behind.
     */
    public function ajukan(PenarikanService $service): void
    {
        $santri = Santri::find($this->santriId);

        if (! $santri) {
            $this->selesai();

            return;
        }

        if (! $this->bisaMandiri()) {
            $this->addError('nominal', 'Penarikan mandiri tidak tersedia di mesin ini. Silakan login dan unggah surat keterangan di halaman Penarikan Tunai, atau tarik tunai lewat mesin lain yang mendukung penarikan.');

            return;
        }

        $saldo = $santri->saldo?->saldo ?? 0;
        $limitInfo = $service->ringkasanLimitHarian($santri);
        $limitHabis = $limitInfo['limit'] !== null && $limitInfo['sisa'] <= 0;

        if ($saldo <= 0 || ! $limitInfo['dalam_jam'] || $limitHabis) {
            $this->addError('nominal', 'Penarikan tidak dapat diajukan saat ini dari halaman ini. Silakan login dan unggah surat keterangan di halaman Penarikan Tunai.');

            return;
        }

        $this->validate(['nominal' => ['required', 'integer', 'min:1']]);

        if ($limitInfo['limit'] !== null && $this->nominal > $limitInfo['sisa']) {
            $this->addError('nominal', 'Nominal melebihi sisa limit harian (Rp '.number_format($limitInfo['sisa'], 0, ',', '.').'). Silakan login dan unggah surat keterangan di halaman Penarikan Tunai untuk mengajukan lebih dari itu.');

            return;
        }

        $this->percobaanFingerprint = 0;
        $this->fingerprint_ref = '';
        $this->step = 'verifikasi_fingerprint';
    }

    public function cairkan(PenarikanService $service): void
    {
        $santri = Santri::find($this->santriId);
        $fingerprintRef = trim($this->fingerprint_ref);
        $this->fingerprint_ref = '';

        if (! $santri || ! $this->nominal) {
            $this->selesai();

            return;
        }

        // Defense in depth: cairkan() is its own public Livewire method and
        // reachable independently of ajukan()'s gate (e.g. a direct wire
        // call), so the device eligibility check is re-asserted here too -
        // never trust that the UI state that got the user this far is
        // still valid.
        if (! $this->bisaMandiri()) {
            $this->step = 'found';
            $this->addError('nominal', 'Penarikan mandiri tidak tersedia di mesin ini.');

            return;
        }

        if ($fingerprintRef === '') {
            return;
        }

        $rateLimitKey = 'kios-cairkan:santri:'.$santri->id;

        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            $this->step = 'rate_limited';

            return;
        }

        RateLimiter::hit($rateLimitKey, 600);

        try {
            $request = $service->ajukanMandiri($santri, $this->nominal, $fingerprintRef, $this->device);
        } catch (InvalidTransaksiException $e) {
            $this->percobaanFingerprint++;

            if ($this->percobaanFingerprint >= self::MAKS_PERCOBAAN_FINGERPRINT) {
                $this->step = 'gagal_terus';

                return;
            }

            $this->addError('fingerprint_ref', $e->getMessage());

            return;
        }

        $this->hasil = [
            'nominal' => $request->nominal_diminta,
            'saldo_setelah' => $santri->saldo?->fresh()?->saldo ?? 0,
            'waktu' => now()->translatedFormat('d M Y, H:i'),
        ];
        $this->nominal = null;
        $this->step = 'selesai_mandiri';
    }

    /** Back out of the fingerprint step to re-enter the nominal, without a full reset (card stays scanned). */
    public function batalFingerprint(): void
    {
        $this->reset(['nominal', 'fingerprint_ref', 'percobaanFingerprint']);
        $this->resetValidation();
        $this->step = 'found';
    }

    public function selesai(): void
    {
        $this->reset(['uid', 'santriId', 'nominal', 'fingerprint_ref', 'percobaanFingerprint', 'hasil']);
        $this->resetValidation();
        $this->step = 'idle';
    }

    /**
     * Self-service withdrawal only makes sense on a registered, currently
     * aktif kiosk_penarikan device - a kiosk_saldo machine has no
     * fingerprint scanner attached, a nonaktif device might be pulled for
     * repair or flagged, and a bare /kios visit (no device at all) has no
     * location to attribute the withdrawal to.
     */
    private function bisaMandiri(): bool
    {
        return $this->device !== null
            && $this->device->tipe === Device::TIPE_KIOSK_PENARIKAN
            && $this->device->status === 'aktif';
    }

    public function render(PenarikanService $service)
    {
        $santri = $this->santriId ? Santri::with('lembaga')->with('saldo')->find($this->santriId) : null;

        return view('livewire.kios.cek-saldo', [
            'title' => 'Cek Saldo & Penarikan',
            'santri' => $santri,
            'bisaMandiri' => $this->bisaMandiri(),
            'saldo' => $santri?->saldo?->saldo ?? 0,
            'limitInfo' => $santri ? $service->ringkasanLimitHarian($santri) : null,
            'riwayat' => $santri
                ? PenarikanRequest::where('santri_id', $santri->id)->where('diminta_at', '>=', now()->subDays(7))->latest('diminta_at')->limit(10)->get()
                : collect(),
        ]);
    }
}
