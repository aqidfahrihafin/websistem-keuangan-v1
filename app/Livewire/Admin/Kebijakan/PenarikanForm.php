<?php

namespace App\Livewire\Admin\Kebijakan;

use App\Livewire\Concerns\WithPerPage;
use App\Models\KebijakanPenarikan;
use App\Models\Lembaga;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class PenarikanForm extends Component
{
    use WithPagination, WithPerPage;

    public string $search = '';

    public bool $showModal = false;

    public ?KebijakanPenarikan $editing = null;

    public string $nama = '';

    public string $jam_mulai = '08:00';

    public string $jam_selesai = '15:00';

    public int $limit_harian = 50000;

    public ?int $applies_lembaga_id = null;

    public string $effective_from = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->editing = null;
        $this->reset(['nama', 'applies_lembaga_id']);
        $this->jam_mulai = '08:00';
        $this->jam_selesai = '15:00';
        $this->limit_harian = 50000;
        $this->effective_from = now()->toDateString();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $kebijakan = KebijakanPenarikan::findOrFail($id);
        $this->editing = $kebijakan;
        $this->nama = $kebijakan->nama;
        $this->jam_mulai = substr($kebijakan->jam_mulai, 0, 5);
        $this->jam_selesai = substr($kebijakan->jam_selesai, 0, 5);
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
            'jam_mulai' => ['required'],
            'jam_selesai' => ['required'],
            'limit_harian' => ['required', 'integer', 'min:1'],
            'applies_lembaga_id' => ['nullable', 'exists:lembagas,id'],
            'effective_from' => ['required', 'date'],
        ]);

        if ($this->editing) {
            $this->editing->update($data);
            $this->showModal = false;
            session()->flash('status', "Kebijakan {$this->editing->nama} berhasil diperbarui.");

            return;
        }

        KebijakanPenarikan::create(array_merge($data, ['is_active' => true]));

        $this->showModal = false;
        session()->flash('status', 'Kebijakan penarikan berhasil disimpan.');
    }

    public function toggleActive(int $id): void
    {
        $kebijakan = KebijakanPenarikan::findOrFail($id);
        $kebijakan->update(['is_active' => ! $kebijakan->is_active]);
    }

    public function hapus(int $id): void
    {
        $kebijakan = KebijakanPenarikan::findOrFail($id);
        $nama = $kebijakan->nama;
        $kebijakan->delete();

        session()->flash('status', "Kebijakan {$nama} berhasil dihapus.");
    }

    public function render()
    {
        $search = trim($this->search);

        return view('livewire.admin.kebijakan.penarikan-form', [
            'title' => 'Kebijakan Penarikan Tunai',
            'lembagas' => Lembaga::orderBy('nama')->get(),
            'kebijakans' => KebijakanPenarikan::query()
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
