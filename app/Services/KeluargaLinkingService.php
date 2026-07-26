<?php

namespace App\Services;

use App\Models\Keluarga;
use App\Models\Santri;
use App\Models\User;
use App\Models\WaliSantri;

class KeluargaLinkingService
{
    /**
     * Recompute auto-generated wali<->santri links for every wali user and every
     * santri that share the given No. KK. Only rows this service created
     * (is_auto_generated = true) are ever added or removed here - manual links
     * created via the admin override screen are left untouched.
     */
    public function syncForNoKk(?string $noKk): void
    {
        if (blank($noKk)) {
            return;
        }

        $keluarga = Keluarga::where('no_kk', $noKk)->first();
        $santriIds = $keluarga ? $keluarga->santris()->pluck('id') : collect();

        // whereHas (not Spatie's role() scope) on purpose: role() throws
        // RoleDoesNotExist if the "wali" role row doesn't exist at all yet
        // (e.g. a fresh install before roles are seeded) - here that should
        // just mean "no wali users", not a crash.
        $waliIds = User::where('no_kk', $noKk)
            ->whereHas('roles', fn ($q) => $q->where('name', 'wali'))
            ->pluck('id');

        foreach ($waliIds as $userId) {
            $existingAutoSantriIds = WaliSantri::where('user_id', $userId)
                ->where('is_auto_generated', true)
                ->pluck('santri_id');

            $toAdd = $santriIds->diff($existingAutoSantriIds);
            $toRemove = $existingAutoSantriIds->diff($santriIds);

            foreach ($toAdd as $santriId) {
                WaliSantri::firstOrCreate(
                    ['user_id' => $userId, 'santri_id' => $santriId],
                    ['hubungan' => 'wali', 'is_auto_generated' => true, 'is_primary' => false]
                );
            }

            if ($toRemove->isNotEmpty()) {
                WaliSantri::where('user_id', $userId)
                    ->where('is_auto_generated', true)
                    ->whereIn('santri_id', $toRemove)
                    ->delete();
            }
        }
    }

    public function syncForUser(User $user, ?string $previousNoKk = null): void
    {
        if ($previousNoKk && $previousNoKk !== $user->no_kk) {
            $this->syncForNoKk($previousNoKk);
        }

        $this->syncForNoKk($user->no_kk);
    }

    public function syncForSantri(Santri $santri, ?string $previousNoKk = null): void
    {
        $currentNoKk = $santri->keluarga?->no_kk;

        if ($previousNoKk && $previousNoKk !== $currentNoKk) {
            $this->syncForNoKk($previousNoKk);
        }

        $this->syncForNoKk($currentNoKk);
    }
}
