<?php

namespace App\Services;

use App\Models\Kamar;
use App\Models\RiwayatKamarSantri;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenempatanKamarService
{
    public function tempatkan(
        Santri $santri,
        ?int $kamarId,
        ?User $petugas,
        ?string $alasan = null,
    ): Santri {
        if (in_array($santri->status, [
            Santri::STATUS_NONAKTIF,
            Santri::STATUS_LULUS,
            Santri::STATUS_KELUAR,
        ], true)) {
            $kamarId = null;
        }

        if ((int) $santri->kamar_id === (int) $kamarId) {
            return $santri;
        }

        return DB::transaction(function () use ($santri, $kamarId, $petugas, $alasan) {
            $santri = Santri::query()->lockForUpdate()->findOrFail($santri->id);
            $kamar = $kamarId ? Kamar::query()->lockForUpdate()->findOrFail($kamarId) : null;

            if ($kamar) {
                if (! $kamar->is_active) {
                    throw ValidationException::withMessages(['kamar_id' => 'Kamar yang dipilih sedang nonaktif.']);
                }

                if ((int) $kamar->lembaga_id !== (int) $santri->lembaga_id) {
                    throw ValidationException::withMessages(['kamar_id' => 'Kamar harus berada pada lembaga santri yang sama.']);
                }

                if ($kamar->jenis_kelamin && $santri->jenis_kelamin && $kamar->jenis_kelamin !== $santri->jenis_kelamin) {
                    throw ValidationException::withMessages(['kamar_id' => 'Jenis kelamin santri tidak sesuai dengan kamar.']);
                }

                $jumlahPenghuni = Santri::query()
                    ->where('kamar_id', $kamar->id)
                    ->whereKeyNot($santri->id)
                    ->count();

                if ($kamar->kapasitas !== null && $jumlahPenghuni >= $kamar->kapasitas) {
                    throw ValidationException::withMessages(['kamar_id' => 'Kamar sudah mencapai kapasitas maksimal.']);
                }
            }

            RiwayatKamarSantri::query()
                ->where('santri_id', $santri->id)
                ->whereNull('tanggal_selesai')
                ->update([
                    'tanggal_selesai' => now()->toDateString(),
                    'alasan_perpindahan' => $alasan ?: ($kamar ? 'Pindah kamar' : 'Keluar dari kamar'),
                ]);

            $santri->update(['kamar_id' => $kamar?->id]);

            if ($kamar) {
                RiwayatKamarSantri::create([
                    'santri_id' => $santri->id,
                    'kamar_id' => $kamar->id,
                    'tanggal_mulai' => now()->toDateString(),
                    'alasan_perpindahan' => $alasan,
                    'dicatat_oleh' => $petugas?->id,
                ]);
            }

            return $santri->fresh(['kamar']);
        });
    }
}
