<?php

namespace App\Livewire\Santri\Tagihan;

use App\Livewire\Concerns\WithPerPage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Index extends Component
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

        return view('livewire.santri.tagihan.index', [
            'title' => 'Tagihan',
            'santri' => $santri,
            'tagihans' => $santri
                ? $santri->tagihans()
                    ->with('jenisTagihan')
                    ->when($search !== '', function ($query) use ($search) {
                        $term = "%{$search}%";

                        $query->where(function ($query) use ($term) {
                            $query
                                ->where('periode_label', 'like', $term)
                                ->orWhere('status', 'like', $term)
                                ->orWhereHas('jenisTagihan', fn ($query) => $query->where('nama', 'like', $term));
                        });
                    })
                    ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
                    ->latest()
                    ->paginate($this->perPage)
                : new LengthAwarePaginator([], 0, $this->perPage),
        ]);
    }
}
