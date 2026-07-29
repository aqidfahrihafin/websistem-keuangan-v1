<?php

namespace App\Services;

use App\Exceptions\LimitKantinHarianException;
use App\Exceptions\SaldoDiBawahMinimumException;
use App\Models\Device;
use App\Models\KebijakanKantin;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Models\UnitUsaha;
use App\Models\UnitUsahaTransaksi;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Orchestrates a kantin purchase: debits the paying santri's saldo and
 * credits the unit usaha's saldo_unit atomically, since WalletService and
 * UnitUsahaWalletService are two separate services operating on two
 * separate ledgers - without this wrapper, a failure on the credit side
 * could leave a santri debited with no corresponding kantin credit.
 *
 * Applies SaldoFloorService's minimum-balance rule, same as
 * TransferSaldoService/TagihanService::bayarDariSaldo() - both this
 * payment and a transfer are wali-initiated through the app (not the
 * santri deciding in person at a physical till), so the floor that
 * protects a santri's pocket money applies here too, for consistency:
 * without it, a wali could drain a santri below the floor via kantin
 * payment even though transfer antar santri is blocked from doing the
 * same.
 */
class KantinPembayaranService
{
    public function __construct(
        private WalletService $wallet,
        private UnitUsahaWalletService $unitUsahaWallet,
        private SaldoFloorService $saldoFloor,
        private KwitansiService $kwitansi,
    ) {}

    /**
     * $diprosesOleh is null for a self-service kiosk purchase (see
     * Kios\BayarKantin) - the same "no logged-in user at all" shape as
     * PenarikanService::ajukanMandiri(); fingerprint match against the
     * santri's own card is the authorization, not a staff login.
     */
    public function bayar(
        Santri $santri,
        UnitUsaha $unitUsaha,
        int $nominal,
        ?User $diprosesOleh,
        ?Device $device = null,
        ?string $requestId = null,
    ): Transaksi {
        if ($unitUsaha->status !== UnitUsaha::STATUS_AKTIF) {
            throw new InvalidArgumentException('Kantin ini sedang tidak aktif dan tidak bisa menerima pembayaran.');
        }

        return DB::transaction(function () use ($santri, $unitUsaha, $nominal, $diprosesOleh, $device, $requestId) {
            // Locked here (same outer transaction) so the floor check reads
            // an up-to-date balance - same ordering as TransferSaldoService
            // and TagihanService::bayarDariSaldo(). Only checked when saldo
            // can cover the payment at all - otherwise debit() below throws
            // its own InsufficientBalanceException, which must win (truly
            // not enough money, vs enough but policy-blocked, are different
            // facts the wali needs to see).
            $saldo = $this->wallet->lockSaldo($santri);

            if ($requestId !== null) {
                $existing = Transaksi::query()
                    ->where('idempotency_key', $requestId)
                    ->where('santri_id', $santri->id)
                    ->first();

                if ($existing) {
                    return $existing->load('kwitansi');
                }
            }
            $minimal = $this->saldoFloor->minimal();

            if ($saldo->saldo >= $nominal && $saldo->saldo - $nominal < $minimal) {
                throw new SaldoDiBawahMinimumException(
                    'Pembayaran tidak bisa dilakukan karena akan membuat saldo '.$santri->nama
                    .' di bawah batas minimum Rp '.number_format($minimal, 0, ',', '.').'.'
                );
            }

            $this->tolakJikaMelebihiLimitHarian($santri, $nominal);

            $debit = $this->wallet->debit(
                $santri,
                $nominal,
                Transaksi::JENIS_PEMBAYARAN_KANTIN,
                [
                    'diproses_oleh' => $diprosesOleh?->id,
                    'referensi_type' => UnitUsaha::class,
                    'referensi_id' => $unitUsaha->id,
                    'idempotency_key' => $requestId,
                    'metadata' => $device ? [
                        'device_id' => $device->id,
                        'kode_device' => $device->kode_device,
                        'lokasi_device' => $device->lokasi,
                    ] : null,
                ]
            );

            $this->unitUsahaWallet->credit(
                $unitUsaha,
                $nominal,
                UnitUsahaTransaksi::JENIS_PEMBAYARAN_MASUK,
                [
                    'transaksi_id' => $debit->id,
                    'dicatat_oleh' => $diprosesOleh?->id,
                ]
            );

            $this->kwitansi->terbitkanUntukKantin($debit);

            return $debit;
        });
    }

    /**
     * Uang jajan harian - unlike the saldo floor (protects a minimum
     * *balance*), this protects a maximum *daily spend* at the kantin,
     * mirroring PenarikanService's limit_harian for cash withdrawal. Only
     * enforced when an active policy actually applies to this santri; with
     * none configured, kantin spending stays unbounded except by saldo
     * itself, same as before this feature existed.
     */
    private function tolakJikaMelebihiLimitHarian(Santri $santri, int $nominal): void
    {
        $ringkasan = $this->ringkasanLimitHarian($santri);

        if ($ringkasan['limit'] === null) {
            return;
        }

        if ($ringkasan['limit'] < $ringkasan['terpakai'] + $nominal) {
            throw new LimitKantinHarianException(
                'Pembayaran ini melebihi batas belanja kantin harian '.$santri->nama
                .' (Rp '.number_format($ringkasan['limit'], 0, ',', '.').'). Sudah terpakai hari ini: Rp '
                .number_format($ringkasan['terpakai'], 0, ',', '.').'.'
            );
        }
    }

    /**
     * @return array{nama: ?string, limit: ?int, terpakai: int, sisa: ?int}
     */
    public function ringkasanLimitHarian(Santri $santri, ?Carbon $sekarang = null): array
    {
        $sekarang ??= now();
        $kebijakan = $this->kebijakanAktifUntuk($santri);
        $terpakai = $this->totalKantinBerhasilHariIni($santri, $sekarang);
        $limit = $kebijakan?->limit_harian;

        return [
            'nama' => $kebijakan?->nama,
            'limit' => $limit,
            'terpakai' => $terpakai,
            'sisa' => $limit === null ? null : max(0, $limit - $terpakai),
        ];
    }

    private function totalKantinBerhasilHariIni(Santri $santri, Carbon $sekarang): int
    {
        $awalHari = $sekarang->copy()->startOfDay();
        $akhirHari = $sekarang->copy()->endOfDay();

        return (int) Transaksi::query()
            ->where('santri_id', $santri->id)
            ->where('jenis', Transaksi::JENIS_PEMBAYARAN_KANTIN)
            ->where('status', Transaksi::STATUS_BERHASIL)
            // A range comparison can use the composite index; wrapping the
            // column in DATE(created_at) forces a scan as history grows.
            ->whereBetween('created_at', [$awalHari, $akhirHari])
            ->sum('nominal');
    }

    private function kebijakanAktifUntuk(Santri $santri): ?KebijakanKantin
    {
        return KebijakanKantin::query()
            ->aktif()
            ->where(function ($q) use ($santri) {
                $q->whereNull('applies_lembaga_id')
                    ->orWhere('applies_lembaga_id', $santri->lembaga_id);
            })
            ->orderByRaw('CASE WHEN applies_lembaga_id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('effective_from')
            ->first();
    }
}
