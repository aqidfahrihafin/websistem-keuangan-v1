<?php

namespace App\Livewire\Pengelola;

use App\Livewire\Concerns\ResolvesPengelolaUnitUsaha;
use App\Livewire\Concerns\WithPerPage;
use App\Models\UnitUsahaTransaksi;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Transaksi extends Component
{
    use ResolvesPengelolaUnitUsaha;
    use WithPagination, WithPerPage;

    public string $search = '';

    public string $arah = '';

    public string $tanggalMulai = '';

    public string $tanggalSelesai = '';

    public function mount(): void
    {
        $this->unitUsaha();
        $this->tanggalMulai = now()->startOfMonth()->toDateString();
        $this->tanggalSelesai = now()->toDateString();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'arah', 'tanggalMulai', 'tanggalSelesai', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $unit = $this->unitUsaha();
        $search = trim($this->search);

        $query = UnitUsahaTransaksi::query()
            ->where('unit_usaha_id', $unit->id)
            ->with(['transaksi.santri', 'transaksi.kwitansi', 'unitUsahaPenarikan'])
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
            ->when($this->arah, fn ($q) => $q->where('arah', $this->arah))
            ->when($this->tanggalMulai, fn ($q) => $q->whereDate('created_at', '>=', $this->tanggalMulai))
            ->when($this->tanggalSelesai, fn ($q) => $q->whereDate('created_at', '<=', $this->tanggalSelesai));

        $ringkasan = (clone $query)->selectRaw(
            "COALESCE(SUM(CASE WHEN arah = 'kredit' THEN nominal ELSE 0 END), 0) as masuk,
             COALESCE(SUM(CASE WHEN arah = 'debit' THEN nominal ELSE 0 END), 0) as keluar"
        )->first();

        return view('livewire.pengelola.transaksi', [
            'title' => 'Transaksi '.$unit->nama,
            'unitUsaha' => $unit,
            'transaksis' => $query->latest()->paginate($this->perPage),
            'totalMasuk' => (int) $ringkasan->masuk,
            'totalKeluar' => (int) $ringkasan->keluar,
        ]);
    }
}
