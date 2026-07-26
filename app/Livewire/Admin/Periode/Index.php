<?php

namespace App\Livewire\Admin\Periode;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Periode;
use App\Models\Tagihan;
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

    public ?Periode $editing = null;

    public string $label = '';

    public ?string $tanggal_mulai = null;

    public ?string $tanggal_selesai = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->editing = null;
        $this->reset(['label', 'tanggal_mulai', 'tanggal_selesai']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $periode = Periode::findOrFail($id);
        $this->editing = $periode;
        $this->label = $periode->label;
        $this->tanggal_mulai = $periode->tanggal_mulai?->toDateString();
        $this->tanggal_selesai = $periode->tanggal_selesai?->toDateString();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'label' => ['required', 'string', 'max:50', Rule::unique('periodes', 'label')->ignore($this->editing?->id)],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        if ($this->editing) {
            $this->editing->update($data);
            $this->showModal = false;
            session()->flash('status', "Periode {$this->editing->label} berhasil diperbarui.");

            return;
        }

        $periode = Periode::create($data + ['is_active' => Periode::count() === 0]);

        $this->showModal = false;
        session()->flash('status', "Periode {$periode->label} berhasil ditambahkan.".($periode->is_active ? ' Otomatis dijadikan periode aktif karena ini periode pertama.' : ''));
    }

    public function aktifkan(int $id): void
    {
        $periode = Periode::findOrFail($id);
        $periode->activate();

        session()->flash('status', "Periode {$periode->label} dijadikan periode aktif.");
    }

    public function hapus(int $id): void
    {
        $periode = Periode::findOrFail($id);

        if ($periode->isExpired()) {
            session()->flash('error', "Periode {$periode->label} sudah melewati tanggal selesai dan tidak bisa dihapus.");

            return;
        }

        $label = $periode->label;
        $periode->delete();

        session()->flash('status', "Periode {$label} berhasil dihapus.");
    }

    public function render()
    {
        Periode::syncExpired();
        $search = trim($this->search);

        return view('livewire.admin.periode.index', [
            'title' => 'Periode Tagihan',
            'periodes' => Periode::query()
                ->when($search !== '', fn ($query) => $query->where('label', 'like', "%{$search}%"))
                ->orderByDesc('label')
                ->paginate($this->perPage),
            'tagihanCounts' => Tagihan::query()->selectRaw('periode_label, count(*) as total')->groupBy('periode_label')->pluck('total', 'periode_label'),
        ]);
    }
}
