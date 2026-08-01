<?php

namespace App\Livewire\Admin\Rayon;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Rayon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Index extends Component
{
    use WithPagination, WithPerPage;

    public string $search = '';
    public bool $showModal = false;
    public ?Rayon $editing = null;
    public string $kode = '';
    public string $nama = '';
    public ?string $alamat = null;
    public ?string $penanggung_jawab = null;
    public bool $is_active = true;

    public function updatedSearch(): void { $this->resetPage(); }

    public function openCreate(): void
    {
        $this->editing = null;
        $this->reset(['kode', 'nama', 'alamat', 'penanggung_jawab']);
        $this->is_active = true;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->editing = Rayon::findOrFail($id);
        $this->fill($this->editing->only(['kode', 'nama', 'alamat', 'penanggung_jawab', 'is_active']));
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'kode' => ['required', 'string', 'max:50', Rule::unique('rayons')->ignore($this->editing?->id)],
            'nama' => ['required', 'string', 'max:255'],
            'alamat' => ['nullable', 'string'],
            'penanggung_jawab' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ]);
        $this->editing ? $this->editing->update($data) : Rayon::create($data);
        $this->showModal = false;
        session()->flash('status', 'Data rayon berhasil disimpan.');
    }

    public function toggleActive(int $id): void
    {
        $rayon = Rayon::findOrFail($id);
        if ($rayon->is_active && ($rayon->santris()->exists() || $rayon->kamars()->exists())) {
            $this->addError('rayon', 'Rayon yang masih memiliki kamar atau santri tidak dapat dinonaktifkan.');
            return;
        }
        $rayon->update(['is_active' => ! $rayon->is_active]);
    }

    public function render()
    {
        $search = trim($this->search);
        return view('livewire.admin.rayon.index', [
            'title' => 'Data Rayon',
            'rayons' => Rayon::query()->withCount(['kamars', 'santris'])
                ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                    ->where('kode', 'like', "%{$search}%")->orWhere('nama', 'like', "%{$search}%")
                    ->orWhere('penanggung_jawab', 'like', "%{$search}%")))
                ->orderBy('nama')->paginate($this->perPage),
        ]);
    }
}
