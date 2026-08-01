<?php

namespace App\Livewire\Unit;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Santri;
use App\Services\UnitAccessService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class SantriIndex extends Component
{
    use WithPagination, WithPerPage;
    public string $search = '';
    #[Url]
    public string $status = '';
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatus(): void { $this->resetPage(); }

    public function render(UnitAccessService $akses)
    {
        $query = $akses->scopeSantri(Santri::query(), auth()->user());
        return view('livewire.unit.santri-index', [
            'title' => 'Data Santri',
            'totalSantri' => (clone $query)->count(),
            'totalAktif' => (clone $query)->where('status', Santri::STATUS_AKTIF)->count(),
            'totalBelumKamar' => (clone $query)->whereNull('kamar_id')->count(),
            'santris' => $query->with(['lembaga:id,nama', 'rayon:id,nama', 'kamar:id,nama'])
                ->when(trim($this->search) !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('nama', 'like', "%{$this->search}%")->orWhere('nis', 'like', "%{$this->search}%")))
                ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
                ->orderBy('nama')->paginate($this->perPage),
        ]);
    }
}
