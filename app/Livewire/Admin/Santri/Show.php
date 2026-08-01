<?php

namespace App\Livewire\Admin\Santri;

use App\Models\Santri;
use App\Models\TransaksiTabungan;
use App\Services\LaporanKeuanganService;
use App\Services\SantriDeaktivasiService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Show extends Component
{
    use WithPagination;

    public Santri $santri;

    public ?string $errorHapus = null;

    // Not the shared WithPerPage trait - two independent paginators here
    // need two independent perPage properties, each resetting only its own
    // page when changed.
    public int $tagihanPerPage = 10;

    public int $transaksiPerPage = 10;

    public int $tabunganPerPage = 10;

    public array $perPageOptions = [10, 25, 50, 100];

    public string $tagihanSearch = '';

    public string $transaksiSearch = '';

    public string $tabunganSearch = '';

    public function mount(Santri $santri): void
    {
        $this->santri = $santri;
    }

    public function updatedTagihanPerPage(): void
    {
        $this->resetPage('tagihanPage');
    }

    public function updatedTransaksiPerPage(): void
    {
        $this->resetPage('transaksiPage');
    }

    public function updatedTabunganPerPage(): void
    {
        $this->resetPage('tabunganPage');
    }

    public function updatingTagihanSearch(): void
    {
        $this->resetPage('tagihanPage');
    }

    public function updatingTransaksiSearch(): void
    {
        $this->resetPage('transaksiPage');
    }

    public function updatingTabunganSearch(): void
    {
        $this->resetPage('tabunganPage');
    }

    public function verifikasi(): void
    {
        $this->santri->update(['status' => Santri::STATUS_AKTIF]);

        session()->flash('status', "{$this->santri->nama} berhasil diverifikasi dan diaktivasi.");
    }

    public function hapus(SantriDeaktivasiService $deaktivasi)
    {
        $this->errorHapus = null;

        if ($alasan = $deaktivasi->alasanTidakBisaDinonaktifkan($this->santri)) {
            $this->errorHapus = ucfirst($alasan);

            return;
        }

        $nama = $this->santri->nama;
        $this->santri->delete();

        session()->flash('status', "{$nama} berhasil dihapus.");

        return $this->redirect(route('admin.santri.index'), navigate: true);
    }

    public function render(LaporanKeuanganService $laporanKeuanganService)
    {
        $this->santri->load([
            'keluarga', 'lembaga', 'rayon', 'kamar', 'kartuSantris', 'walis', 'kategoriDiskon',
            'rekeningTabungan',
            'riwayatKamar' => fn ($query) => $query->with('kamar.rayon')->latest('tanggal_mulai'),
        ]);
        $jenisTransaksiLabel = $laporanKeuanganService->semuaJenisLabel();
        $tagihanSearch = trim($this->tagihanSearch);
        $transaksiSearch = mb_strtolower(trim($this->transaksiSearch));
        $tabunganSearch = mb_strtolower(trim($this->tabunganSearch));
        $jenisTransaksiCocok = collect($jenisTransaksiLabel)
            ->filter(fn (string $label, string $jenis) => $transaksiSearch !== ''
                && (str_contains(mb_strtolower($label), $transaksiSearch)
                    || str_contains(mb_strtolower($jenis), $transaksiSearch)))
            ->keys()
            ->all();

        $tagihans = $this->santri->tagihans()
            ->with('jenisTagihan')
            ->when($tagihanSearch !== '', fn ($query) => $query->where(function ($query) use ($tagihanSearch) {
                $query->where('periode_label', 'like', "%{$tagihanSearch}%")
                    ->orWhere('status', 'like', '%'.str_replace(' ', '_', $tagihanSearch).'%')
                    ->orWhereHas('jenisTagihan', fn ($query) => $query->where('nama', 'like', "%{$tagihanSearch}%"));
            }))
            ->latest()
            ->paginate($this->tagihanPerPage, ['*'], 'tagihanPage');

        $transaksis = $this->santri->transaksis()
            ->when($transaksiSearch !== '', fn ($query) => $query->where(function ($query) use ($transaksiSearch, $jenisTransaksiCocok) {
                $query->where('status', 'like', '%'.str_replace(' ', '_', $transaksiSearch).'%')
                    ->orWhere('arah', 'like', "%{$transaksiSearch}%");

                if ($jenisTransaksiCocok !== []) {
                    $query->orWhereIn('jenis', $jenisTransaksiCocok);
                }
            }))
            ->latest()
            ->paginate($this->transaksiPerPage, ['*'], 'transaksiPage');

        // Ledger tabungan terpisah dari ledger saldo sehingga harus dibaca
        // secara khusus agar setoran tabungan tetap terlihat di detail santri.
        $transaksiTabungan = TransaksiTabungan::query()
            ->whereHas('rekening', fn ($query) => $query->where('santri_id', $this->santri->id))
            ->when($tabunganSearch !== '', fn ($query) => $query->where(function ($query) use ($tabunganSearch) {
                $query->where('jenis', 'like', '%'.str_replace(' ', '_', $tabunganSearch).'%')
                    ->orWhere('kanal', 'like', '%'.str_replace(' ', '_', $tabunganSearch).'%')
                    ->orWhere('status', 'like', '%'.str_replace(' ', '_', $tabunganSearch).'%');
            }))
            ->latest()
            ->paginate($this->tabunganPerPage, ['*'], 'tabunganPage');

        return view('livewire.admin.santri.show', [
            'title' => 'Detail Santri - '.$this->santri->nama,
            'saldo' => $this->santri->saldo?->saldo ?? 0,
            'rekeningTabungan' => $this->santri->rekeningTabungan,
            // Two independent paginators on one page - Livewire keeps them
            // separate in the URL via distinct $pageName args, so paging
            // through tagihan doesn't reset/collide with transaksi.
            'tagihans' => $tagihans,
            'transaksis' => $transaksis,
            'transaksiTabungan' => $transaksiTabungan,
            'tagihanBelumLunasCount' => $this->santri->tagihans()->whereIn('status', ['belum_lunas', 'sebagian'])->count(),
            'jenisTransaksiLabel' => $jenisTransaksiLabel,
        ]);
    }
}
