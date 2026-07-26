<?php

namespace App\Services;

use App\Exceptions\InvalidTransaksiException;
use App\Models\Device;
use App\Models\KebijakanPenarikan;
use App\Models\PenarikanRequest;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PenarikanService
{
    public function __construct(
        private WalletService $wallet,
        private FingerprintVerifier $fingerprint,
        private PushNotificationService $push,
    ) {}

    public function createRequest(Santri $santri, int $nominal): PenarikanRequest
    {
        if ($nominal <= 0) {
            throw new InvalidArgumentException('Nominal penarikan harus lebih besar dari 0.');
        }

        $kebijakan = $this->kebijakanAktifUntuk($santri);
        $sekarang = now();

        $dalamJam = $kebijakan ? $this->dalamJamKebijakan($kebijakan, $sekarang) : true;

        $totalHariIni = $this->totalPenarikanBerhasilHariIni($santri, $sekarang);

        $melebihiLimit = $kebijakan
            ? ($totalHariIni + $nominal) > $kebijakan->limit_harian
            : false;

        $wajibSurat = ! $dalamJam || $melebihiLimit;

        return PenarikanRequest::create([
            'santri_id' => $santri->id,
            'nominal_diminta' => $nominal,
            'status' => PenarikanRequest::STATUS_MENUNGGU,
            'diminta_at' => $sekarang,
            'dalam_jam_kebijakan' => $dalamJam,
            'melebihi_limit_harian' => $melebihiLimit,
            'wajib_surat_keterangan' => $wajibSurat,
        ]);
    }

    public function unggahSuratKeterangan(PenarikanRequest $request, string $path): PenarikanRequest
    {
        $request->update([
            'surat_keterangan_path' => $path,
            'surat_keterangan_status' => PenarikanRequest::SURAT_MENUNGGU_REVIEW,
        ]);

        return $request->fresh();
    }

    public function reviewSuratKeterangan(PenarikanRequest $request, bool $disetujui, User $pengurus): PenarikanRequest
    {
        $request->update([
            'surat_keterangan_status' => $disetujui ? PenarikanRequest::SURAT_DISETUJUI : PenarikanRequest::SURAT_DITOLAK,
        ]);

        return $request->fresh();
    }

    /**
     * $pengurus is null for a self-service kiosk withdrawal (see
     * ajukanMandiri()) - every other case (admin review) passes a real user.
     *
     * $notify defaults to true (admin-reviewed approvals). ajukanMandiri()
     * passes false, since it calls approve() then fulfill() back-to-back in
     * the same request - without this flag a self-service withdrawal would
     * fire "disetujui" and "selesai" (from WalletService::debit()'s own
     * hook) almost simultaneously.
     */
    public function approve(PenarikanRequest $request, ?User $pengurus = null, bool $notify = true): PenarikanRequest
    {
        if ($request->status !== PenarikanRequest::STATUS_MENUNGGU) {
            throw new InvalidTransaksiException('Hanya request berstatus menunggu yang bisa disetujui.');
        }

        if ($request->wajib_surat_keterangan && $request->surat_keterangan_status !== PenarikanRequest::SURAT_DISETUJUI) {
            throw new InvalidTransaksiException('Surat keterangan wajib disetujui terlebih dahulu.');
        }

        $request->update([
            'status' => PenarikanRequest::STATUS_DISETUJUI,
            'diproses_oleh' => $pengurus?->id,
            'diproses_at' => now(),
        ]);

        $request = $request->fresh();

        if ($notify) {
            $this->notifyDisetujui($request);
        }

        return $request;
    }

    private function notifyDisetujui(PenarikanRequest $request): void
    {
        $santri = $request->santri;
        $body = 'Pengajuan penarikan Rp'.number_format($request->nominal_diminta, 0, ',', '.')." untuk {$santri->nama} telah disetujui.";

        foreach ($santri->walis as $wali) {
            $this->push->notify($wali, 'Penarikan Disetujui', $body, [
                'type' => 'penarikan_disetujui',
                'santri_id' => $santri->id,
            ]);
        }
    }

    public function reject(PenarikanRequest $request, User $pengurus, ?string $catatan = null): PenarikanRequest
    {
        $request->update([
            'status' => PenarikanRequest::STATUS_DITOLAK,
            'diproses_oleh' => $pengurus->id,
            'diproses_at' => now(),
            'catatan_petugas' => $catatan,
        ]);

        return $request->fresh();
    }

    /**
     * The only code path allowed to move money for a withdrawal. Requires an
     * approved request, a fingerprint reference that matches the santri's
     * active card, and debits via WalletService (which itself throws if the
     * saldo is insufficient). $pengurus is null for a self-service kiosk
     * withdrawal (see ajukanMandiri()) - the fingerprint check is what
     * authorizes the debit either way, not the presence of a staff actor.
     */
    public function fulfill(PenarikanRequest $request, ?User $pengurus, string $fingerprintRef, ?Device $device = null): PenarikanRequest
    {
        if (! $request->siapDieksekusi()) {
            throw new InvalidTransaksiException('Request penarikan belum siap dieksekusi (belum disetujui atau surat keterangan belum lengkap).');
        }

        if (! $this->fingerprint->verify($fingerprintRef, $request->santri)) {
            throw new InvalidTransaksiException('Verifikasi sidik jari gagal untuk santri ini.');
        }

        return DB::transaction(function () use ($request, $pengurus, $fingerprintRef, $device) {
            $transaksi = $this->wallet->debit(
                $request->santri,
                $request->nominal_diminta,
                Transaksi::JENIS_PENARIKAN_TUNAI,
                [
                    'metode' => Transaksi::METODE_TUNAI,
                    'diproses_oleh' => $pengurus?->id,
                    'referensi_type' => PenarikanRequest::class,
                    'referensi_id' => $request->id,
                ]
            );

            $request->update([
                'status' => PenarikanRequest::STATUS_SELESAI,
                'verifikasi_fingerprint_ref' => $fingerprintRef,
                'device_id' => $device?->id,
                'transaksi_id' => $transaksi->id,
                'diproses_oleh' => $pengurus?->id,
                'diproses_at' => now(),
            ]);

            return $request->fresh();
        });
    }

    /**
     * Fully self-service withdrawal for an in-policy amount - used by the
     * walk-up kiosk screen (Kios\CekSaldo) once a fingerprint has been
     * asserted, with no pengurus involved at all. createRequest() remains
     * the single source of truth for the policy calculation; if it decides
     * this amount needs surat keterangan, the request is cancelled outright
     * (a kiosk session is a poor place to leave a stuck "menunggu" row -
     * the santri can always start a fresh request through the normal
     * login+upload flow) rather than left pending. Reuses approve()/
     * fulfill() as-is, so every existing guard (siapDieksekusi(), the
     * fingerprint check, WalletService::debit()'s own balance check) still
     * applies with no duplicated logic. $device (the physical kiosk this
     * happened at, if any) is threaded straight through to fulfill() for
     * the audit trail - see Kios\CekSaldo::bisaMandiri(), which is what
     * actually gates whether this method is ever reachable for a given
     * device in the first place.
     */
    public function ajukanMandiri(Santri $santri, int $nominal, string $fingerprintRef, ?Device $device = null): PenarikanRequest
    {
        $request = $this->createRequest($santri, $nominal);

        if ($request->wajib_surat_keterangan) {
            $request->update([
                'status' => PenarikanRequest::STATUS_DIBATALKAN,
                'catatan_petugas' => 'Dibatalkan otomatis: di luar kebijakan mandiri kiosk, perlu surat keterangan.',
            ]);

            throw new InvalidTransaksiException(
                'Penarikan ini di luar kebijakan mandiri kiosk dan butuh surat keterangan. Silakan login lalu ajukan dan unggah surat keterangan di halaman Penarikan Tunai.'
            );
        }

        $request = $this->approve($request, notify: false);

        return $this->fulfill($request, null, $fingerprintRef, $device);
    }

    /**
     * Snapshot of today's withdrawal limit for a santri, so the santri-facing
     * UI can show "sisa limit hari ini" without duplicating the policy math
     * that createRequest() uses to decide melebihi_limit_harian/wajib_surat.
     *
     * @return array{kebijakan: ?KebijakanPenarikan, terpakai: int, limit: ?int, sisa: ?int, dalam_jam: bool}
     */
    public function ringkasanLimitHarian(Santri $santri): array
    {
        $kebijakan = $this->kebijakanAktifUntuk($santri);
        $sekarang = now();

        $terpakai = $this->totalPenarikanBerhasilHariIni($santri, $sekarang);
        $limit = $kebijakan?->limit_harian;

        return [
            'kebijakan' => $kebijakan,
            'terpakai' => $terpakai,
            'limit' => $limit,
            'sisa' => $limit !== null ? max(0, $limit - $terpakai) : null,
            'dalam_jam' => $kebijakan ? $this->dalamJamKebijakan($kebijakan, $sekarang) : true,
        ];
    }

    private function totalPenarikanBerhasilHariIni(Santri $santri, Carbon $sekarang): int
    {
        return (int) Transaksi::query()
            ->where('santri_id', $santri->id)
            ->where('jenis', Transaksi::JENIS_PENARIKAN_TUNAI)
            ->where('status', Transaksi::STATUS_BERHASIL)
            ->whereDate('created_at', $sekarang->toDateString())
            ->sum('nominal');
    }

    private function dalamJamKebijakan(KebijakanPenarikan $kebijakan, Carbon $sekarang): bool
    {
        return $sekarang->format('H:i:s') >= $kebijakan->jam_mulai && $sekarang->format('H:i:s') <= $kebijakan->jam_selesai;
    }

    private function kebijakanAktifUntuk(Santri $santri): ?KebijakanPenarikan
    {
        return KebijakanPenarikan::query()
            ->aktif()
            ->where(function ($q) use ($santri) {
                $q->whereNull('applies_lembaga_id')
                    ->orWhere('applies_lembaga_id', $santri->lembaga_id);
            })
            ->orderByDesc('effective_from')
            ->first();
    }
}
