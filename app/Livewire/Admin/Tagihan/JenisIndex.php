<?php

namespace App\Livewire\Admin\Tagihan;

use App\Livewire\Concerns\WithPerPage;
use App\Models\JenisTagihan;
use App\Models\Lembaga;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::app')]
class JenisIndex extends Component
{
    use WithPagination, WithPerPage;

    private const KODE_PREFIX = 'JT';

    public string $search = '';

    public bool $showModal = false;

    public ?JenisTagihan $editing = null;

    public string $kode = '';

    public string $nama = '';

    public ?string $deskripsi = null;

    public int $nominal_default = 0;

    public string $periode = 'bulanan';

    public ?int $lembaga_id = null;

    public bool $is_active = true;

    public bool $berlaku_diskon = false;

    public bool $bisa_dicicil = false;

    // Rendered inside this component's own view rather than via
    // session()->flash() - flash banners live in the outer layout, which
    // Livewire does not re-render on a plain wire:click update.
    public ?string $errorHapus = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->editing = null;
        $this->reset(['nama', 'deskripsi', 'nominal_default', 'lembaga_id', 'berlaku_diskon', 'bisa_dicicil']);
        $this->kode = $this->generateKode();
        $this->periode = 'bulanan';
        $this->is_active = true;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $jenis = JenisTagihan::findOrFail($id);
        $this->editing = $jenis;
        $this->fill($jenis->only(['kode', 'nama', 'deskripsi', 'nominal_default', 'periode', 'lembaga_id', 'is_active', 'berlaku_diskon', 'bisa_dicicil']));
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'nama' => ['required', 'string'],
            'deskripsi' => ['nullable', 'string'],
            'nominal_default' => ['required', 'integer', 'min:0'],
            'periode' => ['required', 'in:bulanan,tahunan,sekali'],
            'lembaga_id' => ['nullable', 'exists:lembagas,id'],
            'is_active' => ['boolean'],
            'berlaku_diskon' => ['boolean'],
            'bisa_dicicil' => ['boolean'],
        ]);

        if ($this->editing) {
            $this->editing->update($data);
        } else {
            $data['kode'] = $this->generateKode();
            JenisTagihan::create($data);
        }

        $this->showModal = false;
        session()->flash('status', 'Jenis tagihan berhasil disimpan.');
    }

    public function toggleActive(int $id): void
    {
        $jenis = JenisTagihan::findOrFail($id);
        $jenis->update(['is_active' => ! $jenis->is_active]);
    }

    /**
     * jenis_tagihans.tagihans is cascadeOnDelete() at the DB level (and
     * tagihans.tagihan_pembayarans cascades further from there) - hard
     * deleting a jenis tagihan that already has generated tagihan would
     * silently wipe real billing/payment history. Only ever safe when
     * truly unused; otherwise nonaktifkan (toggleActive) is the right tool.
     */
    public function hapus(int $id): void
    {
        $this->errorHapus = null;

        $jenis = JenisTagihan::findOrFail($id);

        if ($jenis->tagihans()->exists()) {
            $this->errorHapus = "{$jenis->nama} sudah punya tagihan yang pernah dibuat dan tidak bisa dihapus (akan menghapus riwayat tagihan/pembayaran terkait). Nonaktifkan saja kalau tidak ingin dipakai lagi.";

            return;
        }

        $jenis->delete();
    }

    /**
     * Codes are sequential (JT-001, JT-002, ...) rather than user-entered.
     * Computed in PHP (not raw SQL) so it stays portable between MySQL (dev)
     * and SQLite (tests).
     */
    private function generateKode(): string
    {
        $max = JenisTagihan::query()
            ->where('kode', 'like', self::KODE_PREFIX.'-%')
            ->pluck('kode')
            ->map(fn ($kode) => (int) substr($kode, strlen(self::KODE_PREFIX) + 1))
            ->max();

        return sprintf('%s-%03d', self::KODE_PREFIX, ($max ?? 0) + 1);
    }

    public function render()
    {
        $search = trim($this->search);

        return view('livewire.admin.tagihan.jenis-index', [
            'title' => 'Jenis Tagihan',
            'jenisTagihans' => JenisTagihan::query()
                ->with('lembaga')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('kode', 'like', "%{$search}%")
                            ->orWhere('nama', 'like', "%{$search}%")
                            ->orWhere('deskripsi', 'like', "%{$search}%")
                            ->orWhere('periode', 'like', "%{$search}%")
                            ->orWhereHas('lembaga', fn ($query) => $query->where('nama', 'like', "%{$search}%"));
                    });
                })
                ->orderBy('nama')
                ->paginate($this->perPage),
            'lembagas' => Lembaga::orderBy('nama')->get(),
        ]);
    }
}
