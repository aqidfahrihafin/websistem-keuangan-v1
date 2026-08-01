<?php

namespace App\Services;

use App\Models\Santri;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class UnitAccessService
{
    /** Membatasi query santri berdasarkan seluruh unit aktif milik pengguna. */
    public function scopeSantri(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['superadmin', 'admin'])) return $query;

        if ($user->hasRole('admin_lembaga')) {
            return $query->whereIn('lembaga_id', $this->lembagaIds($user));
        }

        if ($user->hasRole('admin_rayon')) {
            return $query->whereIn('rayon_id', $this->rayonIds($user));
        }

        return $query->whereRaw('1 = 0');
    }

    public function authorizeSantri(User $user, Santri $santri): void
    {
        abort_unless($this->scopeSantri(Santri::query(), $user)->whereKey($santri)->exists(), 403);
    }

    public function lembagaIds(User $user): Collection
    {
        return $user->lembagasDikelola()->pluck('lembagas.id');
    }

    public function rayonIds(User $user): Collection
    {
        return $user->rayonsDikelola()->pluck('rayons.id');
    }
}
