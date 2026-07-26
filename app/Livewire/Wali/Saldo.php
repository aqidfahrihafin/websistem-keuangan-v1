<?php

namespace App\Livewire\Wali;

use App\Livewire\Concerns\ResolvesActiveSantri;
use App\Livewire\Concerns\WithPerPage;
use App\Services\SaldoFloorService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Saldo extends Component
{
    use ResolvesActiveSantri, WithPagination, WithPerPage;

    public string $search = '';

    public string $arah = '';

    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedArah(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render(SaldoFloorService $saldoFloor)
    {
        $santri = $this->resolveActiveSantri();
        $saldo = $santri?->saldo?->saldo ?? 0;
        $minimal = $saldoFloor->minimal();
        $search = trim($this->search);

        return view('livewire.wali.saldo', [
            'title' => 'Saldo & Riwayat Transaksi',
            'santri' => $santri,
            'saldo' => $saldo,
            'minimalSaldo' => $minimal,
            'saldoBisaDigunakan' => max(0, $saldo - $minimal),
            'transaksis' => $santri
                ? $santri->transaksis()
                    ->with('tagihan.jenisTagihan')
                    ->when($search !== '', function ($query) use ($search) {
                        $term = "%{$search}%";

                        $query->where(function ($query) use ($term) {
                            $query
                                ->where('jenis', 'like', $term)
                                ->orWhere('status', 'like', $term)
                                ->orWhere('metode', 'like', $term)
                                ->orWhere('catatan', 'like', $term)
                                ->orWhere('external_reference', 'like', $term)
                                ->orWhereHas('tagihan.jenisTagihan', fn ($query) => $query->where('nama', 'like', $term));
                        });
                    })
                    ->when($this->arah !== '', fn ($query) => $query->where('arah', $this->arah))
                    ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
                    ->latest()
                    ->paginate($this->perPage)
                : null,
        ]);
    }
}
