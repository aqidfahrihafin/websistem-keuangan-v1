<?php

namespace App\Livewire\Santri\Penarikan;

use App\Livewire\Concerns\WithPerPage;
use App\Models\PenarikanRequest;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Riwayat extends Component
{
    use WithPagination, WithPerPage;

    public string $search = '';

    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $santri = Auth::user()->santri;
        $search = trim($this->search);

        return view('livewire.santri.penarikan.riwayat', [
            'title' => 'Riwayat Penarikan Tunai',
            'requests' => $santri
                ? PenarikanRequest::query()
                    ->where('santri_id', $santri->id)
                    ->when($search !== '', function ($query) use ($search) {
                        $term = "%{$search}%";

                        $query->where(function ($query) use ($term) {
                            $query
                                ->where('status', 'like', $term)
                                ->orWhere('catatan_petugas', 'like', $term);
                        });
                    })
                    ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
                    ->latest()
                    ->paginate($this->perPage)
                : null,
        ]);
    }
}
