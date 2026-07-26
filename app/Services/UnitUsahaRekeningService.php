<?php

namespace App\Services;

use App\Exceptions\InvalidTransaksiException;
use App\Models\UnitUsaha;
use App\Models\UnitUsahaRekeningPerubahan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Bank-account-change request state machine for a UnitUsaha's payout
 * destination. Deliberately requires admin approval before a proposed
 * change takes effect (rather than letting the owner self-edit directly) -
 * compromised owner credentials shouldn't be able to silently redirect
 * payouts. Approval itself is the terminal action: it copies the proposed
 * values straight into UnitUsaha's active bank_* fields.
 */
class UnitUsahaRekeningService
{
    /**
     * @param  array{bank_nama: string, bank_no_rekening: string, bank_atas_nama: string}  $data
     */
    public function ajukan(UnitUsaha $unitUsaha, array $data, User $pengelola): UnitUsahaRekeningPerubahan
    {
        if ($unitUsaha->rekeningPerubahanMenunggu()) {
            throw new InvalidTransaksiException('Sudah ada permintaan perubahan rekening yang masih menunggu.');
        }

        return UnitUsahaRekeningPerubahan::create([
            'unit_usaha_id' => $unitUsaha->id,
            'bank_nama_baru' => $data['bank_nama'],
            'bank_no_rekening_baru' => $data['bank_no_rekening'],
            'bank_atas_nama_baru' => $data['bank_atas_nama'],
            'status' => UnitUsahaRekeningPerubahan::STATUS_MENUNGGU,
            'diajukan_oleh' => $pengelola->id,
            'diajukan_at' => now(),
        ]);
    }

    public function approve(UnitUsahaRekeningPerubahan $request, User $admin): UnitUsahaRekeningPerubahan
    {
        if ($request->status !== UnitUsahaRekeningPerubahan::STATUS_MENUNGGU) {
            throw new InvalidTransaksiException('Hanya permintaan berstatus menunggu yang bisa disetujui.');
        }

        return DB::transaction(function () use ($request, $admin) {
            $request->unitUsaha->update([
                'bank_nama' => $request->bank_nama_baru,
                'bank_no_rekening' => $request->bank_no_rekening_baru,
                'bank_atas_nama' => $request->bank_atas_nama_baru,
            ]);

            $request->update([
                'status' => UnitUsahaRekeningPerubahan::STATUS_DISETUJUI,
                'diproses_oleh' => $admin->id,
                'diproses_at' => now(),
            ]);

            return $request->fresh();
        });
    }

    public function reject(UnitUsahaRekeningPerubahan $request, User $admin, ?string $catatan = null): UnitUsahaRekeningPerubahan
    {
        if ($request->status !== UnitUsahaRekeningPerubahan::STATUS_MENUNGGU) {
            throw new InvalidTransaksiException('Hanya permintaan berstatus menunggu yang bisa ditolak.');
        }

        $request->update([
            'status' => UnitUsahaRekeningPerubahan::STATUS_DITOLAK,
            'diproses_oleh' => $admin->id,
            'diproses_at' => now(),
            'catatan_petugas' => $catatan,
        ]);

        return $request->fresh();
    }
}
