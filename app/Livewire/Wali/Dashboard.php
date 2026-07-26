<?php

namespace App\Livewire\Wali;

use App\Livewire\Concerns\ResolvesActiveSantri;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Dashboard extends Component
{
    use ResolvesActiveSantri;

    public string $search = '';

    public string $arah = '';

    public function render()
    {
        $santri = $this->resolveActiveSantri();
        $search = trim($this->search);
        $transaksiTerakhir = collect();

        if ($santri) {
            $transaksiTerakhir = $santri->transaksis()
                ->when($search !== '', function ($query) use ($search) {
                    $term = "%{$search}%";

                    $query->where(function ($query) use ($term) {
                        $query
                            ->where('jenis', 'like', $term)
                            ->orWhere('status', 'like', $term)
                            ->orWhere('metode', 'like', $term)
                            ->orWhere('catatan', 'like', $term)
                            ->orWhere('external_reference', 'like', $term);
                    });
                })
                ->when($this->arah !== '', fn ($query) => $query->where('arah', $this->arah))
                ->latest()
                ->take(5)
                ->get();
        }

        return view('livewire.wali.dashboard', [
            'title' => 'Dashboard Wali',
            'santri' => $santri,
            'saldo' => $santri?->saldo?->saldo ?? 0,
            'tagihanBelumLunas' => $santri?->tagihans()->whereIn('status', ['belum_lunas', 'sebagian'])->count() ?? 0,
            'transaksiTerakhir' => $transaksiTerakhir,
        ]);
    }
}
