<?php

namespace App\Livewire\Admin\Transaksi;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Transaksi;
use App\Models\TransaksiTabungan;
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

    public string $tab = 'saldo';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingJenis(): void
    {
        $this->resetPage();
    }

    public function pilihTab(string $tab): void
    {
        if (! in_array($tab, ['saldo', 'tabungan'], true)) {
            return;
        }

        $this->tab = $tab;
        $this->jenis = '';
        $this->resetPage();
        $this->resetPage('tabungan_page');
    }

    public function render(LaporanKeuanganService $laporanKeuanganService)
    {
        $transaksis = Transaksi::query()
            ->with(['santri', 'tagihan.jenisTagihan', 'mutasiKas.sesiKas', 'penarikanRequest.sesiKas'])
            ->when($this->search, fn ($q) => $q->whereHas('santri', fn ($q) => $q->where(fn ($q) => $q
                ->where('nama', 'like', "%{$this->search}%")
                ->orWhere('nis', 'like', "%{$this->search}%"))))
            ->when($this->jenis, fn ($q) => $q->where('jenis', $this->jenis))
            ->latest()
            ->paginate($this->perPage);

        $transaksiTabungan = TransaksiTabungan::query()
            ->with(['rekening.santri', 'sesiKas'])
            ->when($this->search, fn ($q) => $q->whereHas('rekening.santri', fn ($q) => $q->where(fn ($q) => $q
                ->where('nama', 'like', "%{$this->search}%")
                ->orWhere('nis', 'like', "%{$this->search}%"))))
            ->when($this->jenis, fn ($q) => $q->where('jenis', $this->jenis))
            ->latest()
            ->paginate($this->perPage, ['*'], 'tabungan_page');

        return view('livewire.admin.transaksi.index', [
            'title' => 'Riwayat Transaksi',
            'transaksis' => $transaksis,
            'transaksiTabungan' => $transaksiTabungan,
            'jenisTransaksiLabel' => $laporanKeuanganService->semuaJenisLabel(),
        ]);
    }
}
