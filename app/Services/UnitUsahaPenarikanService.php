<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidTransaksiException;
use App\Models\UnitUsaha;
use App\Models\UnitUsahaPenarikan;
use App\Models\UnitUsahaTransaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Withdrawal state machine for a UnitUsaha's collected saldo_unit - modeled
 * on PenarikanService's menunggu -> disetujui -> selesai (or -> ditolak)
 * shape, deliberately without the surat-keterangan/kebijakan-jam/fingerprint
 * machinery there (santri-pocket-money-specific policy, not relevant here).
 * Every step (request, approve, reject, fulfill) happens inside the
 * admin-only Kelola Kantin area, so $diminta/$pengurus are always real users
 * - there's no self-service/kiosk path like PenarikanRequest's ajukanMandiri().
 */
class UnitUsahaPenarikanService
{
    public function __construct(
        private UnitUsahaWalletService $wallet,
    ) {}

    public function createRequest(
        UnitUsaha $unitUsaha,
        int $nominal,
        User $diminta,
        string $metode = UnitUsahaPenarikan::METODE_TRANSFER_BANK,
    ): UnitUsahaPenarikan
    {
        if ($nominal <= 0) {
            throw new InvalidArgumentException('Nominal penarikan harus lebih besar dari 0.');
        }

        // Unlike PenarikanService's santri-side equivalent (fulfilled almost
        // immediately at a kiosk), a kantin request can sit pending for
        // days waiting on admin review + a manual bank transfer - letting
        // one through with no saldo behind it at all is just confusing,
        // even though fulfill() would still safely reject it later via
        // UnitUsahaWalletService::debit().
        if ($nominal > $unitUsaha->saldo_unit) {
            throw new InsufficientBalanceException('Saldo unit usaha tidak mencukupi untuk penarikan ini.');
        }

        if (! in_array($metode, [UnitUsahaPenarikan::METODE_TRANSFER_BANK, UnitUsahaPenarikan::METODE_TUNAI], true)) {
            throw new InvalidArgumentException('Metode pencairan tidak valid.');
        }

        return UnitUsahaPenarikan::create([
            'unit_usaha_id' => $unitUsaha->id,
            'nominal_diminta' => $nominal,
            'metode_pencairan' => $metode,
            'bank_nama_tujuan' => $metode === UnitUsahaPenarikan::METODE_TRANSFER_BANK ? $unitUsaha->bank_nama : null,
            'bank_no_rekening_tujuan' => $metode === UnitUsahaPenarikan::METODE_TRANSFER_BANK ? $unitUsaha->bank_no_rekening : null,
            'bank_atas_nama_tujuan' => $metode === UnitUsahaPenarikan::METODE_TRANSFER_BANK ? $unitUsaha->bank_atas_nama : null,
            'status' => UnitUsahaPenarikan::STATUS_MENUNGGU,
            'diminta_oleh' => $diminta->id,
            'diminta_at' => now(),
        ]);
    }

    public function approve(UnitUsahaPenarikan $request, User $pengurus): UnitUsahaPenarikan
    {
        if ($request->status !== UnitUsahaPenarikan::STATUS_MENUNGGU) {
            throw new InvalidTransaksiException('Hanya permintaan berstatus menunggu yang bisa disetujui.');
        }

        $request->update([
            'status' => UnitUsahaPenarikan::STATUS_DISETUJUI,
            'diproses_oleh' => $pengurus->id,
            'diproses_at' => now(),
            'kode_serah_terima' => $request->metode_pencairan === UnitUsahaPenarikan::METODE_TUNAI
                ? (string) random_int(100000, 999999)
                : null,
        ]);

        return $request->fresh();
    }

    public function reject(UnitUsahaPenarikan $request, User $pengurus, ?string $catatan = null): UnitUsahaPenarikan
    {
        if ($request->status !== UnitUsahaPenarikan::STATUS_MENUNGGU) {
            throw new InvalidTransaksiException('Hanya permintaan berstatus menunggu yang bisa ditolak.');
        }

        $request->update([
            'status' => UnitUsahaPenarikan::STATUS_DITOLAK,
            'diproses_oleh' => $pengurus->id,
            'diproses_at' => now(),
            'catatan_petugas' => $catatan,
        ]);

        return $request->fresh();
    }

    /**
     * The only code path allowed to actually move money out of a unit
     * usaha's saldo_unit. Requires an approved request; debits via
     * UnitUsahaWalletService (which itself throws if saldo_unit is
     * insufficient). Disbursement itself happens outside the system (admin
     * transfers via their own bank/e-wallet) - $referensiTransfer is the
     * proof of that transfer and is required, not just a free-text note,
     * so a fulfilled request always has something to show for it (see also
     * InvoiceService::kantinPenarikan() for the printable kwitansi).
     */
    public function fulfill(
        UnitUsahaPenarikan $request,
        User $pengurus,
        ?string $referensiTransfer = null,
        ?string $kodeSerahTerima = null,
    ): UnitUsahaPenarikan
    {
        if ($request->status !== UnitUsahaPenarikan::STATUS_DISETUJUI) {
            throw new InvalidTransaksiException('Permintaan penarikan belum disetujui.');
        }

        if ($request->metode_pencairan === UnitUsahaPenarikan::METODE_TRANSFER_BANK && trim((string) $referensiTransfer) === '') {
            throw new InvalidArgumentException('Nomor referensi transfer wajib diisi sebagai bukti pencairan.');
        }

        if ($request->metode_pencairan === UnitUsahaPenarikan::METODE_TUNAI
            && trim((string) $kodeSerahTerima) !== $request->kode_serah_terima) {
            throw new InvalidArgumentException('Kode serah-terima tunai tidak sesuai.');
        }

        return DB::transaction(function () use ($request, $pengurus, $referensiTransfer) {
            $ledger = $this->wallet->debit(
                $request->unitUsaha,
                $request->nominal_diminta,
                UnitUsahaTransaksi::JENIS_PENARIKAN_KELUAR,
                [
                    'unit_usaha_penarikan_id' => $request->id,
                    'dicatat_oleh' => $pengurus->id,
                ]
            );

            $request->update([
                'status' => UnitUsahaPenarikan::STATUS_SELESAI,
                'referensi_transfer' => $request->metode_pencairan === UnitUsahaPenarikan::METODE_TRANSFER_BANK
                    ? trim((string) $referensiTransfer)
                    : null,
                'unit_usaha_transaksi_id' => $ledger->id,
                'diproses_oleh' => $pengurus->id,
                'diproses_at' => now(),
                'diserahkan_at' => now(),
            ]);

            return $request->fresh();
        });
    }

    public function confirmReceived(UnitUsahaPenarikan $request, User $pengelola): UnitUsahaPenarikan
    {
        if ($request->status !== UnitUsahaPenarikan::STATUS_SELESAI || ! $request->diserahkan_at) {
            throw new InvalidTransaksiException('Dana penarikan belum diserahkan oleh petugas.');
        }

        if ($request->dikonfirmasi_at) {
            throw new InvalidTransaksiException('Penerimaan dana sudah dikonfirmasi.');
        }

        if ((int) $request->unitUsaha->pengelola_user_id !== (int) $pengelola->id) {
            throw new InvalidTransaksiException('Anda tidak berhak mengonfirmasi penarikan ini.');
        }

        $request->update([
            'dikonfirmasi_oleh' => $pengelola->id,
            'dikonfirmasi_at' => now(),
        ]);

        return $request->fresh();
    }
}
