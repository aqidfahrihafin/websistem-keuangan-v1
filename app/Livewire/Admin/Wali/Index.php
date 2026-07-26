<?php

namespace App\Livewire\Admin\Wali;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Santri;
use App\Models\User;
use App\Models\WaliSantri;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Index extends Component
{
    use WithPagination, WithPerPage;

    public string $user_search = '';

    public ?int $user_id = null;

    public string $santri_search = '';

    public ?int $santri_id = null;

    public string $listSearch = '';

    public string $hubungan = 'wali';

    public bool $showLinkModal = false;

    public ?int $expandedWaliId = null;

    public function updatingListSearch(): void
    {
        $this->resetPage();
    }

    public function tautkan(): void
    {
        $data = $this->validate([
            'user_id' => ['required', 'exists:users,id'],
            'santri_id' => ['required', 'exists:santris,id'],
            'hubungan' => ['required', 'in:ayah,ibu,wali,kerabat,lainnya'],
        ]);

        WaliSantri::firstOrCreate(
            ['user_id' => $data['user_id'], 'santri_id' => $data['santri_id']],
            ['hubungan' => $data['hubungan'], 'is_auto_generated' => false, 'is_primary' => false]
        );

        session()->flash('status', 'Wali berhasil ditautkan ke santri.');
        $this->reset(['user_id', 'santri_id', 'user_search', 'santri_search']);
        $this->showLinkModal = false;
    }

    public function openLinkModal(): void
    {
        $this->reset(['user_id', 'santri_id', 'user_search', 'santri_search']);
        $this->resetValidation();
        $this->showLinkModal = true;
    }

    public function toggleWaliDetail(int $waliId): void
    {
        $this->expandedWaliId = $this->expandedWaliId === $waliId ? null : $waliId;
    }

    public function hapus(int $id): void
    {
        WaliSantri::findOrFail($id)->delete();
        session()->flash('status', 'Tautan wali-santri dihapus.');
    }

    public function render()
    {
        $listSearch = trim($this->listSearch);

        return view('livewire.admin.wali.index', [
            'title' => 'Wali Santri',
            'users' => $this->user_search ? User::role('wali')->where('name', 'like', "%{$this->user_search}%")->limit(10)->get() : collect(),
            'santris' => $this->santri_search ? Santri::where('nama', 'like', "%{$this->santri_search}%")->orWhere('nis', 'like', "%{$this->santri_search}%")->limit(10)->get() : collect(),
            // Grouped by wali (one row per wali, all their santri listed
            // together) rather than one row per WaliSantri pair - a wali
            // with several anak used to repeat their name on every row.
            'waliList' => User::role('wali')
                ->whereHas('waliSantris')
                ->when($listSearch !== '', fn ($query) => $query->where(function ($query) use ($listSearch) {
                    $query->where('name', 'like', "%{$listSearch}%")
                        ->orWhere('email', 'like', "%{$listSearch}%")
                        ->orWhere('phone', 'like', "%{$listSearch}%")
                        ->orWhere('no_kk', 'like', "%{$listSearch}%")
                        ->orWhereHas('waliSantris.santri', fn ($query) => $query->where(fn ($query) => $query
                            ->where('nama', 'like', "%{$listSearch}%")
                            ->orWhere('nis', 'like', "%{$listSearch}%")
                            ->orWhereHas('lembaga', fn ($query) => $query->where('nama', 'like', "%{$listSearch}%"))));
                }))
                ->with(['waliSantris' => fn ($q) => $q->with('santri.lembaga')->oldest()])
                ->orderBy('name')
                ->paginate($this->perPage),
        ]);
    }
}
