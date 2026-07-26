<?php

namespace App\Livewire\Admin\Lembaga;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Lembaga;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class Index extends Component
{
    use WithPagination, WithPerPage;

    private const KODE_PREFIX = 'LBG';

    public string $search = '';

    public bool $showModal = false;

    public ?Lembaga $editing = null;

    public string $kode = '';

    public string $nama = '';

    public string $tipe = 'sekolah_formal';

    public ?string $alamat = null;

    public bool $is_active = true;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->editing = null;
        $this->reset(['nama', 'tipe', 'alamat']);
        $this->kode = $this->generateKode();
        $this->is_active = true;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $lembaga = Lembaga::findOrFail($id);
        $this->editing = $lembaga;
        $this->fill($lembaga->only(['kode', 'nama', 'tipe', 'alamat', 'is_active']));
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'nama' => ['required', 'string'],
            'tipe' => ['required', 'in:pondok_pusat,sekolah_formal,lainnya'],
            'alamat' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        if ($this->editing) {
            $this->editing->update($data);
        } else {
            $data['kode'] = $this->generateKode();
            Lembaga::create($data);
        }

        $this->showModal = false;
        session()->flash('status', 'Lembaga berhasil disimpan.');
    }

    public function toggleActive(int $id): void
    {
        $lembaga = Lembaga::findOrFail($id);
        $lembaga->update(['is_active' => ! $lembaga->is_active]);
    }

    /**
     * Codes are sequential (LBG-001, LBG-002, ...) rather than user-entered.
     * Computed in PHP (not raw SQL) so it stays portable between MySQL (dev)
     * and SQLite (tests).
     */
    private function generateKode(): string
    {
        $max = Lembaga::query()
            ->where('kode', 'like', self::KODE_PREFIX.'-%')
            ->pluck('kode')
            ->map(fn ($kode) => (int) substr($kode, strlen(self::KODE_PREFIX) + 1))
            ->max();

        return sprintf('%s-%03d', self::KODE_PREFIX, ($max ?? 0) + 1);
    }

    public function render()
    {
        $search = trim($this->search);

        return view('livewire.admin.lembaga.index', [
            'title' => 'Lembaga',
            'lembagas' => Lembaga::query()
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('kode', 'like', "%{$search}%")
                            ->orWhere('nama', 'like', "%{$search}%")
                            ->orWhere('tipe', 'like', "%{$search}%")
                            ->orWhere('alamat', 'like', "%{$search}%");
                    });
                })
                ->orderBy('nama')
                ->paginate($this->perPage),
        ]);
    }
}
