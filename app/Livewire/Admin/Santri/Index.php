<?php

namespace App\Livewire\Admin\Santri;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Santri;
use App\Services\SantriDeaktivasiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Index extends Component
{
    use WithPagination, WithPerPage;

    public string $search = '';

    #[Url]
    public string $status = '';

    // Rendered inside this component's own view rather than via
    // session()->flash() - flash banners live in the outer layout, which
    // Livewire does not re-render on a plain wire:click update (only this
    // component's own root gets morphed), so a flash set here would only
    // ever surface after a full page navigation.
    public ?string $errorHapus = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function verifikasi(int $id): void
    {
        $santri = Santri::findOrFail($id);
        $santri->update(['status' => Santri::STATUS_AKTIF]);

        session()->flash('status', "{$santri->nama} berhasil diverifikasi dan diaktivasi.");
    }

    public function hapus(int $id, SantriDeaktivasiService $deaktivasi): void
    {
        $this->errorHapus = null;

        $santri = Santri::findOrFail($id);

        if ($alasan = $deaktivasi->alasanTidakBisaDinonaktifkan($santri)) {
            $this->errorHapus = "{$santri->nama} {$alasan}";

            return;
        }

        $santri->delete();
    }

    public function render()
    {
        $santris = Santri::query()
            ->with(['keluarga', 'lembaga', 'kamar', 'kategoriDiskon'])
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                    $q->where('nama', 'like', "%{$this->search}%")
                    ->orWhere('nis', 'like', "%{$this->search}%")
                    ->orWhereHas('kamar', fn ($q) => $q->where('nama', 'like', "%{$this->search}%")
                        ->orWhere('kode', 'like', "%{$this->search}%"));
            }))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderBy('nama')
            ->paginate($this->perPage);

        return view('livewire.admin.santri.index', [
            'title' => 'Data Santri',
            'santris' => $santris,
        ]);
    }
}
