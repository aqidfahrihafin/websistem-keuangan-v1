<?php

namespace App\Services;

use App\Models\Rayon;
use App\Models\RiwayatRayonSantri;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenempatanRayonService
{
    public function tempatkan(Santri $santri, ?int $rayonId, ?User $pencatat, ?string $alasan = null): void
    {
        if ((int) $santri->rayon_id === (int) $rayonId) return;

        DB::transaction(function () use ($santri, $rayonId, $pencatat, $alasan) {
            $rayon = $rayonId ? Rayon::query()->where('is_active', true)->find($rayonId) : null;
            if ($rayonId && ! $rayon) {
                throw ValidationException::withMessages(['rayon_id' => 'Rayon yang dipilih tidak aktif.']);
            }

            RiwayatRayonSantri::query()->where('santri_id', $santri->id)
                ->whereNull('tanggal_selesai')->update(['tanggal_selesai' => now()->subDay()->toDateString()]);

            // Kamar lama tidak boleh tertinggal ketika santri berpindah rayon.
            if ($santri->kamar_id) app(PenempatanKamarService::class)->tempatkan($santri, null, $pencatat, 'Perpindahan rayon');
            $santri->update(['rayon_id' => $rayon?->id]);

            if ($rayon) {
                RiwayatRayonSantri::create([
                    'santri_id' => $santri->id,
                    'rayon_id' => $rayon->id,
                    'tanggal_mulai' => now()->toDateString(),
                    'alasan_perpindahan' => $alasan,
                    'dicatat_oleh' => $pencatat?->id,
                ]);
            }
        });
    }
}
