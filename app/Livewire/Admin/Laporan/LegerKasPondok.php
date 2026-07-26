<?php

namespace App\Livewire\Admin\Laporan;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Lembaga;
use App\Models\Periode;
use App\Services\LegerKasPondokService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class LegerKasPondok extends Component
{
    use WithPagination, WithPerPage;

    public const KUSTOM = '__kustom__';

    public string $periode_pilihan = '';

    public string $tanggal_dari = '';

    public string $tanggal_sampai = '';

    public ?int $lembaga_id = null;

    public string $sumber_dana = '';

    public string $search = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTanggalDari(): void
    {
        $this->resetPage();
    }

    public function updatingTanggalSampai(): void
    {
        $this->resetPage();
    }

    public function updatingLembagaId(): void
    {
        $this->resetPage();
    }

    public function updatingSumberDana(): void
    {
        $this->resetPage();
    }

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
        $this->resetPage();
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

    public function render(LegerKasPondokService $service)
    {
        $leger = $service->generate(
            Carbon::parse($this->tanggal_dari),
            Carbon::parse($this->tanggal_sampai),
            $this->lembaga_id,
            $this->sumber_dana ?: null,
        );

        // saldo_berjalan on every row already depends on every prior row in
        // the FULL date range (a running balance), and saldo_awal/akhir/
        // totals above are aggregates over that same full range - none of
        // that can be recomputed from a single page's slice, so generate()
        // above always runs unsliced. Only the on-screen listing of
        // $leger['entri'] is paginated, as a manual wrap (same reasoning as
        // Admin\Backup\Index - this is a plain computed array, not an
        // Eloquent query paginate() can run against).
        $allEntri = collect($leger['entri']);

        // Client-side-feeling search over an already-computed in-memory
        // array (not a real query) - entri is small enough per report range
        // that filtering here, after generate() has already built it, is
        // simpler than threading a search term through every entriXxx()
        // method in the service for what's ultimately just a display
        // convenience, not a data boundary.
        if (($needle = mb_strtolower(trim($this->search))) !== '') {
            $allEntri = $allEntri->filter(
                fn (array $row) => str_contains(mb_strtolower($row['jenis']), $needle)
                    || str_contains(mb_strtolower($row['pihak']), $needle)
                    || str_contains(mb_strtolower($row['keterangan']), $needle)
                    || str_contains(mb_strtolower(str_replace('_', ' ', $row['sumber_dana'])), $needle)
            )->values();
        }

        $page = $this->getPage();

        $entriPaginated = new LengthAwarePaginator(
            $allEntri->forPage($page, $this->perPage)->values(),
            $allEntri->count(),
            $this->perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );

        return view('livewire.admin.laporan.leger-kas-pondok', [
            'title' => 'Leger Kas Pondok',
            'leger' => $leger,
            'entriPaginated' => $entriPaginated,
            'lembagas' => Lembaga::orderBy('nama')->get(),
            'periodes' => Periode::orderByDesc('label')->get(),
            'isKustom' => $this->periode_pilihan === self::KUSTOM,
        ]);
    }
}
