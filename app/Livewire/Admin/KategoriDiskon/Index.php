<?php

namespace App\Livewire\Admin\KategoriDiskon;

use App\Livewire\Concerns\WithPerPage;
use App\Models\KategoriDiskon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Index extends Component
{
    use WithPagination, WithPerPage;

    public string $search = '';

    public bool $showModal = false;

    public ?KategoriDiskon $editing = null;

    public string $nama = '';

    public int $persentase = 0;

    public bool $is_active = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->editing = null;
        $this->reset(['nama', 'persentase']);
        $this->is_active = true;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $kategori = KategoriDiskon::findOrFail($id);
        $this->editing = $kategori;
        $this->fill($kategori->only(['nama', 'persentase', 'is_active']));
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'nama' => ['required', 'string', 'max:255'],
            'persentase' => ['required', 'integer', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ]);

        if ($this->editing) {
            $this->editing->update($data);
        } else {
            KategoriDiskon::create($data);
        }

        $this->showModal = false;
        session()->flash('status', 'Kategori diskon berhasil disimpan.');
    }

    public function toggleActive(int $id): void
    {
        $kategori = KategoriDiskon::findOrFail($id);
        $kategori->update(['is_active' => ! $kategori->is_active]);
    }

    public function render()
    {
        $search = trim($this->search);

        return view('livewire.admin.kategori-diskon.index', [
            'title' => 'Kategori Diskon Tagihan',
            'kategoris' => KategoriDiskon::query()
                ->withCount('santris')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('nama', 'like', "%{$search}%")
                            ->orWhere('kode', 'like', "%{$search}%");
                    });
                })
                ->orderBy('nama')
                ->paginate($this->perPage),
        ]);
    }
}
