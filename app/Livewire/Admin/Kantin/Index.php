<?php

namespace App\Livewire\Admin\Kantin;

use App\Exceptions\InvalidTransaksiException;
use App\Livewire\Concerns\WithPerPage;
use App\Models\UnitUsaha;
use App\Services\PengelolaAccountService;
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

    public bool $showQr = false;

    public ?UnitUsaha $qrUnit = null;

    public ?UnitUsaha $editing = null;

    public string $kode = '';

    public string $nama = '';

    public string $tipe = UnitUsaha::TIPE_KANTIN;

    public bool $aktif = true;

    public string $bank_nama = '';

    public string $bank_no_rekening = '';

    public string $bank_atas_nama = '';

    public bool $showPengelolaModal = false;

    public ?UnitUsaha $pengelolaUnit = null;

    public string $pengelola_nama = '';

    public string $pengelola_email = '';

    public string $pengelola_phone = '';

    public ?string $generatedPassword = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->editing = null;
        $this->reset(['kode', 'nama', 'bank_nama', 'bank_no_rekening', 'bank_atas_nama']);
        $this->tipe = UnitUsaha::TIPE_KANTIN;
        $this->aktif = true;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $unitUsaha = UnitUsaha::findOrFail($id);
        $this->editing = $unitUsaha;
        $this->fill($unitUsaha->only(['kode', 'nama', 'tipe']));
        // bank_* columns are nullable (a new kantin may not have payout
        // details yet), but the form fields are plain strings - coerce
        // null to '' rather than typing them ?string just for this.
        $this->bank_nama = $unitUsaha->bank_nama ?? '';
        $this->bank_no_rekening = $unitUsaha->bank_no_rekening ?? '';
        $this->bank_atas_nama = $unitUsaha->bank_atas_nama ?? '';
        $this->aktif = $unitUsaha->status === UnitUsaha::STATUS_AKTIF;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'kode' => [
                'required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-_]+$/',
                Rule::unique('unit_usahas', 'kode')->ignore($this->editing?->id),
            ],
            'nama' => ['required', 'string'],
            'tipe' => ['required', 'in:'.implode(',', [
                UnitUsaha::TIPE_KANTIN, UnitUsaha::TIPE_KOPERASI, UnitUsaha::TIPE_LAINNYA,
            ])],
            'bank_nama' => ['nullable', 'string', 'max:100'],
            'bank_no_rekening' => ['nullable', 'string', 'max:50'],
            'bank_atas_nama' => ['nullable', 'string', 'max:100'],
        ], [
            'kode.regex' => 'Kode hanya boleh berisi huruf, angka, tanda hubung (-), dan garis bawah (_) - kode ini yang dibaca dari kode QR.',
        ]);

        $data['status'] = $this->aktif ? UnitUsaha::STATUS_AKTIF : UnitUsaha::STATUS_NONAKTIF;

        // Admin's own edits (including bank_* here) write directly - only
        // changes proposed by the pengelola themselves go through
        // UnitUsahaRekeningService's approval flow (see Pengelola\Rekening).
        if ($this->editing) {
            $this->editing->update($data);
        } else {
            $data['saldo_unit'] = 0;
            UnitUsaha::create($data);
        }

        $this->showModal = false;
        session()->flash('status', 'Data unit usaha berhasil disimpan.');
    }

    public function openBuatAkunPengelola(int $id): void
    {
        $this->pengelolaUnit = UnitUsaha::findOrFail($id);
        $this->reset(['pengelola_nama', 'pengelola_email', 'pengelola_phone']);
        $this->generatedPassword = null;
        $this->resetValidation();
        $this->showPengelolaModal = true;
    }

    public function buatAkunPengelola(PengelolaAccountService $service): void
    {
        $data = $this->validate([
            'pengelola_nama' => ['required', 'string', 'max:100'],
            'pengelola_email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'pengelola_phone' => ['nullable', 'string', 'max:30'],
        ]);

        try {
            $result = $service->buatAkunDenganSandiAcak(
                $this->pengelolaUnit,
                $data['pengelola_nama'],
                $data['pengelola_email'],
                $data['pengelola_phone'] ?: null,
            );
            $this->generatedPassword = $result['password'];
            $this->pengelolaUnit = $this->pengelolaUnit->fresh();
        } catch (InvalidTransaksiException $e) {
            $this->addError('pengelola_nama', $e->getMessage());
        }
    }

    public function toggleActive(int $id): void
    {
        $unitUsaha = UnitUsaha::findOrFail($id);
        $unitUsaha->update([
            'status' => $unitUsaha->status === UnitUsaha::STATUS_AKTIF ? UnitUsaha::STATUS_NONAKTIF : UnitUsaha::STATUS_AKTIF,
        ]);
    }

    public function bukaQr(int $id): void
    {
        $this->qrUnit = UnitUsaha::findOrFail($id);
        $this->showQr = true;
    }

    public function render()
    {
        $search = trim($this->search);

        return view('livewire.admin.kantin.index', [
            'title' => 'Kelola Kantin',
            'unitUsahas' => UnitUsaha::query()
                ->with('pengelola')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('kode', 'like', "%{$search}%")
                            ->orWhere('nama', 'like', "%{$search}%")
                            ->orWhere('tipe', 'like', "%{$search}%")
                            ->orWhere('bank_nama', 'like', "%{$search}%")
                            ->orWhere('bank_no_rekening', 'like', "%{$search}%")
                            ->orWhere('bank_atas_nama', 'like', "%{$search}%")
                            ->orWhereHas('pengelola', function ($query) use ($search) {
                                $query->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    });
                })
                ->orderBy('nama')
                ->paginate($this->perPage),
            'tipeLabel' => [
                UnitUsaha::TIPE_KANTIN => 'Kantin',
                UnitUsaha::TIPE_KOPERASI => 'Koperasi',
                UnitUsaha::TIPE_LAINNYA => 'Lainnya',
            ],
        ]);
    }
}
