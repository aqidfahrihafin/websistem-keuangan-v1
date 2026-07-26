<?php

namespace App\Livewire\Admin\Laporan;

use App\Models\Lembaga;
use App\Models\Periode;
use App\Services\LaporanKeuanganService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Keuangan extends Component
{
    public const KUSTOM = '__kustom__';

    public string $periode_pilihan = '';

    public string $tanggal_dari = '';

    public string $tanggal_sampai = '';

    public ?int $lembaga_id = null;

    public string $transaksiSearch = '';

    public string $tagihanSearch = '';

    public function mount(): void
    {
        Periode::syncExpired();
        $aktif = Periode::where('is_active', true)->first();

        if ($aktif) {
            $this->pilihPeriode($aktif->label);
        } else {
            $this->periode_pilihan = self::KUSTOM;
            $this->tanggal_dari = now()->startOfMonth()->toDateString();
            $this->tanggal_sampai = now()->endOfMonth()->toDateString();
        }
    }

    public function updatedPeriodePilihan(): void
    {
        $this->pilihPeriode($this->periode_pilihan);
    }

    private function pilihPeriode(string $label): void
    {
        $this->periode_pilihan = $label;

        if ($label === self::KUSTOM) {
            return;
        }

        $periode = Periode::where('label', $label)->first();

        if ($periode) {
            $this->tanggal_dari = $periode->tanggal_mulai?->toDateString() ?? now()->startOfMonth()->toDateString();
            $this->tanggal_sampai = $periode->tanggal_selesai?->toDateString() ?? now()->endOfMonth()->toDateString();
        }
    }

    public function render(LaporanKeuanganService $service)
    {
        $laporan = $service->generate(
            Carbon::parse($this->tanggal_dari),
            Carbon::parse($this->tanggal_sampai),
            $this->lembaga_id
        );
        $transaksiSearch = mb_strtolower(trim($this->transaksiSearch));
        $tagihanSearch = mb_strtolower(trim($this->tagihanSearch));

        $transaksiRows = collect($laporan['transaksi']['per_jenis'])
            ->when($transaksiSearch !== '', fn ($rows) => $rows
                ->filter(fn (array $row) => str_contains(mb_strtolower($row['label']), $transaksiSearch)
                    || str_contains(mb_strtolower($row['jenis']), $transaksiSearch))
                ->values());

        $tagihanRows = collect($laporan['tagihan']['per_jenis'])
            ->when($tagihanSearch !== '', fn ($rows) => $rows
                ->filter(fn (array $row) => str_contains(mb_strtolower($row['nama']), $tagihanSearch))
                ->values());

        return view('livewire.admin.laporan.keuangan', [
            'title' => 'Laporan Keuangan',
            'laporan' => $laporan,
            'transaksiRows' => $transaksiRows,
            'tagihanRows' => $tagihanRows,
            'lembagas' => Lembaga::orderBy('nama')->get(),
            'periodes' => Periode::orderByDesc('label')->get(),
            'isKustom' => $this->periode_pilihan === self::KUSTOM,
        ]);
    }
}
