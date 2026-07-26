<?php

namespace App\Livewire\Admin\Kantin;

use App\Livewire\Concerns\WithPerPage;
use App\Models\UnitUsaha;
use App\Models\UnitUsahaTransaksi;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Read-only browser for a kantin's own ledger (unit_usaha_transaksis) -
 * separate from the santri-scoped `transaksis` table entirely (see
 * KantinPembayaranService). Previously only visible 5-rows-at-a-time on a
 * pengelola's own dashboard; this gives admin a full, filterable view
 * across every unit usaha.
 */
#[Layout('layouts::app')]
class Ledger extends Component
{
    use WithPagination, WithPerPage;

    public string $search = '';

    public string $unit_usaha_id = '';

    public string $jenis = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedUnitUsahaId(): void
    {
        $this->resetPage();
    }

    public function updatedJenis(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $search = trim($this->search);

        $transaksis = UnitUsahaTransaksi::query()
            ->with(['unitUsaha', 'dicatatOleh', 'transaksi.kwitansi'])
            ->when($this->unit_usaha_id, fn ($q) => $q->where('unit_usaha_id', $this->unit_usaha_id))
            ->when($this->jenis, fn ($q) => $q->where('jenis', $this->jenis))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('unitUsaha', function ($query) use ($search) {
                        $query->where('nama', 'like', "%{$search}%")
                            ->orWhere('kode', 'like', "%{$search}%");
                    })->orWhereHas('dicatatOleh', function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                });
            })
            ->latest()
            ->paginate($this->perPage);

        return view('livewire.admin.kantin.ledger', [
            'title' => 'Riwayat Transaksi Kantin',
            'transaksis' => $transaksis,
            'unitUsahas' => UnitUsaha::orderBy('nama')->get(),
        ]);
    }
}
