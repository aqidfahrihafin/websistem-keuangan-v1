<?php

namespace App\Livewire\Admin\Transaksi;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Transaksi;
use App\Services\LaporanKeuanganService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Index extends Component
{
    use WithPagination, WithPerPage;

    public string $search = '';

    public string $jenis = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingJenis(): void
    {
        $this->resetPage();
    }

    public function render(LaporanKeuanganService $laporanKeuanganService)
    {
        $transaksis = Transaksi::query()
            ->with(['santri', 'tagihan.jenisTagihan'])
            ->when($this->search, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where(fn ($q) => $q
                ->where('nama', 'like', "%{$this->search}%")
                ->orWhere('nis', 'like', "%{$this->search}%"))))
            ->when($this->jenis, fn ($q) => $q->where('jenis', $this->jenis))
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.transaksi.index', [
            'title' => 'Riwayat Transaksi',
            'transaksis' => $transaksis,
            'jenisTransaksiLabel' => $laporanKeuanganService->semuaJenisLabel(),
        ]);
    }
}
