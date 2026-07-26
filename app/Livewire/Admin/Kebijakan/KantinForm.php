<?php

namespace App\Livewire\Admin\Kebijakan;

use App\Livewire\Concerns\WithPerPage;
use App\Models\KebijakanKantin;
use App\Models\Lembaga;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class KantinForm extends Component
{
    use WithPagination, WithPerPage;

    public string $search = '';

    public bool $showModal = false;

    /** Plain id, not an Eloquent model - see Banner\Index::$editingId for why. */
    public ?int $editingId = null;

    public string $nama = '';

    public int $limit_harian = 20000;

    public ?int $applies_lembaga_id = null;

    public string $effective_from = '';

    public ?string $statusMessage = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->reset(['nama', 'applies_lembaga_id']);
        $this->limit_harian = 20000;
        $this->effective_from = now()->toDateString();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $kebijakan = KebijakanKantin::findOrFail($id);
        $this->editingId = $id;
        $this->nama = $kebijakan->nama;
        $this->limit_harian = $kebijakan->limit_harian;
        $this->applies_lembaga_id = $kebijakan->applies_lembaga_id;
        $this->effective_from = $kebijakan->effective_from->toDateString();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function simpan(): void
    {
        $data = $this->validate([
            'nama' => ['required', 'string'],
            'limit_harian' => ['required', 'integer', 'min:1'],
            'applies_lembaga_id' => ['nullable', 'exists:lembagas,id'],
            'effective_from' => ['required', 'date'],
        ]);

        $editing = $this->editingId ? KebijakanKantin::find($this->editingId) : null;

        if ($editing) {
            $editing->update($data);
            $this->showModal = false;
            $this->statusMessage = "Kebijakan {$editing->nama} berhasil diperbarui.";

            return;
        }

        KebijakanKantin::create(array_merge($data, ['is_active' => true]));

        $this->showModal = false;
        $this->statusMessage = 'Kebijakan kantin berhasil disimpan.';
    }

    public function toggleActive(int $id): void
    {
        $kebijakan = KebijakanKantin::findOrFail($id);
        $kebijakan->update(['is_active' => ! $kebijakan->is_active]);
    }

    public function hapus(int $id): void
    {
        $kebijakan = KebijakanKantin::findOrFail($id);
        $nama = $kebijakan->nama;
        $kebijakan->delete();

        if ($this->editingId === $id) {
            $this->editingId = null;
        }

        $this->statusMessage = "Kebijakan {$nama} berhasil dihapus.";
    }

    public function render()
    {
        $search = trim($this->search);

        return view('livewire.admin.kebijakan.kantin-form', [
            'title' => 'Kebijakan Belanja Kantin',
            'lembagas' => Lembaga::orderBy('nama')->get(),
            'kebijakans' => KebijakanKantin::query()
                ->with('appliesLembaga')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('nama', 'like', "%{$search}%")
                            ->orWhereHas('appliesLembaga', fn ($query) => $query->where('nama', 'like', "%{$search}%"));
                    });
                })
                ->latest()
                ->paginate($this->perPage),
        ]);
    }
}
