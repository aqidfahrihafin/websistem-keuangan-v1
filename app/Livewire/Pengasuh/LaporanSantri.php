<?php

namespace App\Livewire\Pengasuh;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Santri;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class LaporanSantri extends Component
{
    use WithPagination, WithPerPage;

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $santris = Santri::query()
            ->with(['saldo', 'lembaga'])
            ->withCount(['tagihans as tagihan_belum_lunas_count' => fn ($q) => $q->whereIn('status', ['belum_lunas', 'sebagian'])])
            ->when($this->search, fn ($q) => $q->where('nama', 'like', "%{$this->search}%")->orWhere('nis', 'like', "%{$this->search}%"))
            ->orderBy('nama')
            ->paginate($this->perPage);

        return view('livewire.pengasuh.laporan-santri', [
            'title' => 'Laporan Santri',
            'santris' => $santris,
        ]);
    }
}
