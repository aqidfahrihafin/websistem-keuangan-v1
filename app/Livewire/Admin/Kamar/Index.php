<?php

namespace App\Livewire\Admin\Kamar;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Kamar;
use App\Models\Lembaga;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Index extends Component
{
    use WithPagination, WithPerPage;

    public string $search = '';
    public ?int $filterLembaga = null;
    public bool $showModal = false;
    public ?Kamar $editing = null;
    public ?int $lembaga_id = null;
    public string $kode = '';
    public string $nama = '';
    public ?string $gedung = null;
    public ?int $lantai = null;
    public ?int $kapasitas = null;
    public ?string $jenis_kelamin = null;
    public bool $is_active = true;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterLembaga(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->editing = null;
        $this->reset(['lembaga_id', 'kode', 'nama', 'gedung', 'lantai', 'kapasitas', 'jenis_kelamin']);
        $this->is_active = true;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $this->editing = Kamar::findOrFail($id);
        $this->fill($this->editing->only([
            'lembaga_id', 'kode', 'nama', 'gedung', 'lantai',
            'kapasitas', 'jenis_kelamin', 'is_active',
        ]));
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'lembaga_id' => ['required', 'exists:lembagas,id'],
            'kode' => [
                'required', 'string', 'max:50',
                Rule::unique('kamars', 'kode')
                    ->where(fn ($query) => $query->where('lembaga_id', $this->lembaga_id))
                    ->ignore($this->editing?->id),
            ],
            'nama' => ['required', 'string', 'max:255'],
            'gedung' => ['nullable', 'string', 'max:255'],
            'lantai' => ['nullable', 'integer', 'min:0', 'max:100'],
            'kapasitas' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'jenis_kelamin' => ['nullable', 'in:L,P'],
            'is_active' => ['boolean'],
        ]);

        if ($this->editing && $data['kapasitas'] !== null && $this->editing->santris()->count() > $data['kapasitas']) {
            $this->addError('kapasitas', 'Kapasitas tidak boleh lebih kecil dari jumlah penghuni saat ini.');
            return;
        }

        $this->editing ? $this->editing->update($data) : Kamar::create($data);
        $this->showModal = false;
        session()->flash('status', 'Data kamar berhasil disimpan.');
    }

    public function toggleActive(int $id): void
    {
        $kamar = Kamar::findOrFail($id);

        if ($kamar->is_active && $kamar->santris()->exists()) {
            $this->addError('kamar', 'Kamar yang masih memiliki penghuni tidak dapat dinonaktifkan.');
            return;
        }

        $kamar->update(['is_active' => ! $kamar->is_active]);
    }

    public function render()
    {
        $search = trim($this->search);

        return view('livewire.admin.kamar.index', [
            'title' => 'Data Kamar',
            'lembagas' => Lembaga::query()->where('is_active', true)->orderBy('nama')->get(),
            'kamars' => Kamar::query()
                ->with('lembaga:id,nama')
                ->withCount('santris')
                ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                    $query->where('kode', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%")
                        ->orWhere('gedung', 'like', "%{$search}%")
                        ->orWhereHas('lembaga', fn ($query) => $query->where('nama', 'like', "%{$search}%"));
                }))
                ->when($this->filterLembaga, fn ($query) => $query->where('lembaga_id', $this->filterLembaga))
                ->orderBy('nama')
                ->paginate($this->perPage),
        ]);
    }
}
