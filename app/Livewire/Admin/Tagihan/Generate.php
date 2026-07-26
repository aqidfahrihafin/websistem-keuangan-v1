<?php

namespace App\Livewire\Admin\Tagihan;

use App\Models\JenisTagihan;
use App\Models\Lembaga;
use App\Models\Periode;
use App\Models\Santri;
use App\Services\TagihanService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Generate extends Component
{
    public ?int $jenis_tagihan_id = null;

    public string $periode_label = '';

    public ?string $jatuh_tempo = null;

    public ?int $nominal_override = null;

    public string $mode = 'semua';

    public string $santri_search = '';

    public array $selected_santri_ids = [];

    public ?int $filter_lembaga_id = null;

    public function mount(): void
    {
        Periode::syncExpired();

        $this->periode_label = Periode::where('is_active', true)->value('label') ?? '';
    }

    public function updatingMode(): void
    {
        $this->selected_santri_ids = [];
        $this->santri_search = '';
        $this->filter_lembaga_id = null;
    }

    public function toggleSantri(int $santriId): void
    {
        if (in_array($santriId, $this->selected_santri_ids, true)) {
            $this->selected_santri_ids = array_values(array_diff($this->selected_santri_ids, [$santriId]));
        } else {
            $this->selected_santri_ids[] = $santriId;
        }
    }

    public function hapusSantriTerpilih(int $santriId): void
    {
        $this->selected_santri_ids = array_values(array_diff($this->selected_santri_ids, [$santriId]));
    }

    public function generate(TagihanService $service): void
    {
        $this->validate([
            'jenis_tagihan_id' => ['required', 'exists:jenis_tagihans,id'],
            'periode_label' => ['required', 'string'],
            'jatuh_tempo' => ['nullable', 'date'],
            'nominal_override' => ['nullable', 'integer', 'min:0'],
            'selected_santri_ids' => $this->mode === 'pilih' ? ['required', 'array', 'min:1'] : ['array'],
            'filter_lembaga_id' => $this->mode === 'lembaga' ? ['required', 'exists:lembagas,id'] : ['nullable'],
        ], [
            'selected_santri_ids.required' => 'Pilih minimal satu santri.',
            'selected_santri_ids.min' => 'Pilih minimal satu santri.',
            'filter_lembaga_id.required' => 'Pilih lembaga terlebih dahulu.',
        ]);

        $jenis = JenisTagihan::findOrFail($this->jenis_tagihan_id);

        $santriIds = match ($this->mode) {
            'pilih' => $this->selected_santri_ids,
            'lembaga' => Santri::where('status', Santri::STATUS_AKTIF)
                ->where('lembaga_id', $this->filter_lembaga_id)
                ->pluck('id')
                ->all(),
            default => null,
        };

        $hasil = $service->generateTagihanForPeriode(
            $jenis,
            $this->periode_label,
            $this->jatuh_tempo ? Carbon::parse($this->jatuh_tempo) : null,
            Auth::user(),
            $this->nominal_override,
            $santriIds,
        );

        // Redirect (not just an in-place update) so the form comes back
        // completely fresh for the next batch instead of keeping stale
        // selections/search state around - the result summary rides along
        // as a flash message, same pattern as every other admin create
        // action in this app.
        session()->flash('status', "{$hasil['dibuat']} tagihan baru dibuat, {$hasil['dilewati']} sudah ada sebelumnya (dilewati).");
        $this->redirect(route('admin.tagihan.generate'), navigate: false);
    }

    public function render()
    {
        return view('livewire.admin.tagihan.generate', [
            'title' => 'Generate Tagihan',
            'jenisTagihans' => JenisTagihan::where('is_active', true)->orderBy('nama')->get(),
            'periodes' => Periode::orderByDesc('label')->get(),
            'lembagas' => Lembaga::where('is_active', true)->orderBy('nama')->get(),
            'santriHasil' => $this->mode === 'pilih' && $this->santri_search !== ''
                ? Santri::where('status', Santri::STATUS_AKTIF)
                    ->where(fn ($q) => $q->where('nama', 'like', "%{$this->santri_search}%")->orWhere('nis', 'like', "%{$this->santri_search}%"))
                    ->orderBy('nama')
                    ->limit(20)
                    ->get()
                : collect(),
            'santriTerpilih' => $this->mode === 'pilih' && count($this->selected_santri_ids)
                ? Santri::whereIn('id', $this->selected_santri_ids)->orderBy('nama')->get()
                : collect(),
        ]);
    }
}
