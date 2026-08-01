<?php

namespace App\Livewire\Admin\Perangkat;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Device;
use App\Models\UnitUsaha;
use App\Models\User;
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

    public bool $showPanduan = false;

    public ?Device $panduan = null;

    public ?Device $editing = null;

    public string $kode_device = '';

    public string $nama = '';

    public ?string $lokasi = null;

    public string $tipe = Device::TIPE_KIOSK_SALDO;

    public ?int $unit_usaha_id = null;

    public bool $aktif = true;

    /** @var array<int, int|string> */
    public array $petugas_ids = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->editing = null;
        $this->reset(['kode_device', 'nama', 'lokasi', 'unit_usaha_id', 'petugas_ids']);
        $this->tipe = Device::TIPE_KIOSK_SALDO;
        $this->aktif = true;
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $device = Device::with('petugasTerdaftar')->findOrFail($id);
        $this->editing = $device;
        $this->fill($device->only(['kode_device', 'nama', 'lokasi', 'tipe', 'unit_usaha_id']));
        $this->aktif = $device->status === 'aktif';
        $this->petugas_ids = $device->petugasTerdaftar->pluck('id')->all();
        $this->resetValidation();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'kode_device' => [
                'required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-_]+$/',
                Rule::unique('devices', 'kode_device')->ignore($this->editing?->id),
            ],
            'nama' => ['required', 'string'],
            'lokasi' => ['nullable', 'string'],
            'tipe' => ['required', 'in:'.implode(',', [Device::TIPE_KIOSK_SALDO, Device::TIPE_KIOSK_PENARIKAN, Device::TIPE_KANTIN])],
            // Only meaningful (and required) for a kantin kiosk - every
            // other tipe leaves this null regardless of what's selected in
            // the form, since the field is hidden for them anyway.
            'unit_usaha_id' => [$this->tipe === Device::TIPE_KANTIN ? 'required' : 'nullable', 'exists:unit_usahas,id'],
            'petugas_ids' => ['array'],
            'petugas_ids.*' => ['integer', 'exists:users,id'],
        ], [
            'kode_device.regex' => 'Kode device hanya boleh berisi huruf, angka, tanda hubung (-), dan garis bawah (_) - kode ini menjadi bagian dari URL mesin.',
            'unit_usaha_id.required' => 'Pilih unit usaha untuk kiosk kantin ini.',
        ]);

        if ($this->tipe !== Device::TIPE_KANTIN) {
            $data['unit_usaha_id'] = null;
        }

        $data['status'] = $this->aktif ? 'aktif' : 'nonaktif';

        $sesiAktif = $this->editing?->sesiKasAktif()->first();
        if ($sesiAktif && (! $this->aktif || ! in_array($sesiAktif->petugas_id, array_map('intval', $this->petugas_ids), true))) {
            $this->addError('petugas_ids', 'Perangkat masih memiliki sesi aktif. Petugas aktif tidak boleh dilepas dan perangkat tidak boleh dinonaktifkan sebelum sesi ditutup.');
            return;
        }

        if ($this->editing) {
            $this->editing->update($data);
            $device = $this->editing;
        } else {
            $device = Device::create($data);
        }

        $petugas = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'petugas_kios'))
            ->whereIn('id', $this->petugas_ids)
            ->pluck('id');
        $device->petugasTerdaftar()->sync($petugas->mapWithKeys(fn ($id) => [$id => [
            'aktif' => true,
            'ditugaskan_oleh' => auth()->id(),
            'ditugaskan_at' => now(),
        ]])->all());

        $this->showModal = false;
        session()->flash('status', 'Perangkat berhasil disimpan.');
    }

    /**
     * The Device record itself already carries everything the physical
     * kiosk-mode browser + RFID/fingerprint hardware needs (kode_device
     * drives the URL, tipe/status gate self-service in Kios\CekSaldo) - so
     * "setting" a device is a one-time physical/OS-level procedure, not
     * anything this app stores. This just walks the admin through that
     * procedure for a specific device instead of leaving them to reverse
     * it out of documentation.
     */
    public function bukaPanduan(int $id): void
    {
        $this->panduan = Device::findOrFail($id);
        $this->showPanduan = true;
    }

    public function toggleActive(int $id): void
    {
        $device = Device::findOrFail($id);
        if ($device->status === 'aktif' && $device->sesiKasAktif()->exists()) {
            $this->addError('perangkat', 'Perangkat tidak dapat dinonaktifkan karena masih memiliki sesi kas aktif.');
            return;
        }
        $device->update(['status' => $device->status === 'aktif' ? 'nonaktif' : 'aktif']);
    }

    /**
     * Plain-PHP "last seen" label from the existing last_seen_at column - no
     * new schema, no separate heartbeat mechanism for the web /kios path
     * (it's updated on every card scan, see Kios\CekSaldo::scan()).
     */
    private function statusTerakhir(Device $device): string
    {
        if (! $device->last_seen_at) {
            return 'Belum pernah digunakan';
        }

        if ($device->last_seen_at->diffInMinutes(now()) < 5) {
            return 'Online';
        }

        if ($device->last_seen_at->diffInHours(now()) < 1) {
            return 'Baru saja aktif';
        }

        return 'Terakhir aktif '.$device->last_seen_at->diffForHumans();
    }

    public function render()
    {
        $search = trim($this->search);
        $devices = Device::query()
            ->with(['petugasJaga', 'petugasTerdaftar', 'sesiKasAktif.petugas', 'unitUsaha'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('kode_device', 'like', "%{$search}%")
                        ->orWhere('nama', 'like', "%{$search}%")
                        ->orWhere('lokasi', 'like', "%{$search}%")
                        ->orWhere('tipe', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('petugasJaga', function ($query) use ($search) {
                            $query->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('unitUsaha', function ($query) use ($search) {
                            $query->where('nama', 'like', "%{$search}%")
                                ->orWhere('kode', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('nama')
            ->paginate($this->perPage);

        return view('livewire.admin.perangkat.index', [
            'title' => 'Perangkat',
            'devices' => $devices,
            'statusTerakhir' => $devices->getCollection()->mapWithKeys(fn (Device $d) => [$d->id => $this->statusTerakhir($d)]),
            'tipeLabel' => [
                Device::TIPE_KIOSK_SALDO => 'Kiosk Cek Saldo',
                Device::TIPE_KIOSK_PENARIKAN => 'Kiosk Penarikan (Mandiri)',
                Device::TIPE_KANTIN => 'Kiosk Kantin',
            ],
            'unitUsahas' => UnitUsaha::orderBy('nama')->get(),
            'petugasKios' => User::query()
                ->whereHas('roles', fn ($q) => $q->where('name', 'petugas_kios'))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }
}
