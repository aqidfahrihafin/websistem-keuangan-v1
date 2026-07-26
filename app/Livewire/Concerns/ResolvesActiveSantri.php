<?php

namespace App\Livewire\Concerns;

use App\Models\Santri;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

trait ResolvesActiveSantri
{
    protected function anakAsuh(): Collection
    {
        return Auth::user()->anakAsuh()->orderBy('nama')->get();
    }

    protected function resolveActiveSantri(): ?Santri
    {
        $anak = $this->anakAsuh();

        if ($anak->isEmpty()) {
            return null;
        }

        $activeId = session('wali.active_santri_id');
        $active = $activeId ? $anak->firstWhere('id', $activeId) : null;

        if (! $active) {
            $active = $anak->first();
            session(['wali.active_santri_id' => $active->id]);
        }

        return $active;
    }
}
