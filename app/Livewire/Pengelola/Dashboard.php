<?php

namespace App\Livewire\Pengelola;

use App\Livewire\Concerns\ResolvesPengelolaUnitUsaha;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Dashboard extends Component
{
    use ResolvesPengelolaUnitUsaha;

    public string $search = '';

    public string $arah = '';

    public function mount(): void
    {
        $this->unitUsaha();
    }

    public function render()
    {
        $unitUsaha = $this->unitUsaha();
        $search = trim($this->search);

        $transaksiTerakhir = $unitUsaha->transaksis()
            ->with(['transaksi.santri', 'transaksi.kwitansi'])
            ->when($search !== '', function ($query) use ($search) {
                $term = "%{$search}%";

                $query->where(function ($query) use ($term) {
                    $query
                        ->where('jenis', 'like', $term)
                        ->orWhereHas('transaksi.santri', function ($query) use ($term) {
                            $query->where(function ($query) use ($term) {
                                $query
                                    ->where('nama', 'like', $term)
                                    ->orWhere('nis', 'like', $term);
                            });
                        })
                        ->orWhereHas('unitUsahaPenarikan', fn ($query) => $query->where('referensi_transfer', 'like', $term));
                });
            })
            ->when($this->arah !== '', fn ($query) => $query->where('arah', $this->arah))
            ->latest()
            ->take(5)
            ->get();

        return view('livewire.pengelola.dashboard', [
            'title' => 'Dashboard Pengelola',
            'unitUsaha' => $unitUsaha,
            'transaksiTerakhir' => $transaksiTerakhir,
            'pemasukanHariIni' => (int) $unitUsaha->transaksis()
                ->where('arah', 'kredit')->whereDate('created_at', today())->sum('nominal'),
            'jumlahTransaksiHariIni' => $unitUsaha->transaksis()
                ->where('arah', 'kredit')->whereDate('created_at', today())->count(),
            'rekeningMenunggu' => $unitUsaha->rekeningPerubahanMenunggu(),
        ]);
    }
}
