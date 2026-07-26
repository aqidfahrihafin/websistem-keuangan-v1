<?php

namespace App\Livewire\Santri;

use App\Livewire\Concerns\WithPerPage;
use App\Services\LaporanKeuanganService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Saldo extends Component
{
    use WithPagination, WithPerPage;

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

    public function render(LaporanKeuanganService $laporanKeuanganService)
    {
        $santri = Auth::user()->santri;
        $search = trim($this->search);

        return view('livewire.santri.saldo', [
            'title' => 'Saldo & Riwayat Transaksi',
            'santri' => $santri,
            'saldo' => $santri?->saldo?->saldo ?? 0,
            'transaksis' => $santri
                ? $santri->transaksis()
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
                    ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
                    ->latest()
                    ->paginate($this->perPage)
                : null,
            'jenisTransaksiLabel' => $laporanKeuanganService->semuaJenisLabel(),
        ]);
    }
}
