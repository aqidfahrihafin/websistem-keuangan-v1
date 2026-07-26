<?php

namespace App\Livewire\Admin\Keluarga;

use App\Livewire\Concerns\WithPerPage;
use App\Models\Keluarga;
use App\Models\User;
use App\Services\KeluargaLinkingService;
use App\Services\WaliAccountService;
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

    public ?Keluarga $editing = null;

    public string $no_kk = '';

    public ?string $nama_kepala_keluarga = null;

    public ?string $nik_kepala_keluarga = null;

    public ?string $tempat_lahir_kepala_keluarga = null;

    public ?string $tanggal_lahir_kepala_keluarga = null;

    public ?string $alamat = null;

    public bool $noKkDicek = false;

    public ?Keluarga $keluargaDitemukan = null;

    public bool $showDetailModal = false;

    public ?Keluarga $detailKeluarga = null;

    public bool $showWaliModal = false;

    public ?Keluarga $keluargaUntukWali = null;

    public string $wali_name = '';

    public ?string $wali_email = null;

    public ?string $wali_phone = null;

    public ?string $wali_password = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->editing = null;
        $this->reset([
            'no_kk', 'nama_kepala_keluarga', 'nik_kepala_keluarga',
            'tempat_lahir_kepala_keluarga', 'tanggal_lahir_kepala_keluarga',
            'alamat', 'noKkDicek', 'keluargaDitemukan',
        ]);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $keluarga = Keluarga::findOrFail($id);
        $this->editing = $keluarga;
        $this->no_kk = $keluarga->no_kk;
        $this->nama_kepala_keluarga = $keluarga->nama_kepala_keluarga;
        $this->nik_kepala_keluarga = $keluarga->nik_kepala_keluarga;
        $this->tempat_lahir_kepala_keluarga = $keluarga->tempat_lahir_kepala_keluarga;
        $this->tanggal_lahir_kepala_keluarga = $keluarga->tanggal_lahir_kepala_keluarga?->toDateString();
        $this->alamat = $keluarga->alamat;
        $this->noKkDicek = true;
        $this->keluargaDitemukan = null;
        $this->resetValidation();
        $this->showModal = true;
    }

    /**
     * The "No. KK first" flow: check whether a keluarga already exists for
     * this No. KK before revealing the create fields, so admin sees existing
     * family data instead of blindly retyping it (and risking a duplicate
     * or an accidental overwrite of the real nama_kepala_keluarga).
     */
    public function cekNoKk(): void
    {
        $this->validate(['no_kk' => ['required', 'digits:16']]);

        $this->keluargaDitemukan = Keluarga::where('no_kk', $this->no_kk)->first();
        $this->noKkDicek = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'no_kk' => ['required', 'digits:16', Rule::unique('keluargas', 'no_kk')->ignore($this->editing?->id)],
            'nama_kepala_keluarga' => ['required', 'string', 'max:255'],
            'nik_kepala_keluarga' => ['nullable', 'digits:16', Rule::unique('keluargas', 'nik_kepala_keluarga')->ignore($this->editing?->id)],
            'tempat_lahir_kepala_keluarga' => ['nullable', 'string'],
            'tanggal_lahir_kepala_keluarga' => ['nullable', 'date'],
            'alamat' => ['nullable', 'string'],
        ]);

        if ($this->editing) {
            $this->editing->update($data);
        } else {
            Keluarga::create($data);
        }

        $this->showModal = false;
        session()->flash('status', 'Data keluarga berhasil disimpan.');
    }

    public function toggleDetail(int $id): void
    {
        $this->detailKeluarga = Keluarga::query()
            ->with(['santris', 'waliUsers'])
            ->withCount(['santris', 'waliUsers'])
            ->findOrFail($id);
        $this->showDetailModal = true;
    }

    /**
     * Shortcut for the most common gap on this page: a keluarga with santri
     * but no wali account yet. Creates the User (role wali) with this
     * keluarga's No. KK pre-filled and auto-links it (see the explicit
     * syncForUser call below for why that can't just rely on the
     * UserObserver's created() hook alone).
     */
    public function openBuatWali(int $keluargaId): void
    {
        $this->keluargaUntukWali = Keluarga::findOrFail($keluargaId);
        $this->reset(['wali_name', 'wali_email', 'wali_phone', 'wali_password']);
        $this->resetValidation();
        $this->showWaliModal = true;
    }

    public function simpanWali(KeluargaLinkingService $linking): void
    {
        $data = $this->validate([
            'wali_name' => ['required', 'string', 'max:255'],
            'wali_email' => ['nullable', 'email', 'unique:users,email'],
            'wali_phone' => ['nullable', 'string'],
            'wali_password' => ['required', 'string', 'min:8'],
        ]);

        $wali = User::create([
            'name' => $data['wali_name'],
            'email' => $data['wali_email'],
            'phone' => $data['wali_phone'],
            'password' => $data['wali_password'],
            'no_kk' => $this->keluargaUntukWali->no_kk,
        ]);
        $wali->assignRole('wali');

        // UserObserver::created() already tried to auto-link when the user
        // row was inserted above, but the "wali" role wasn't assigned yet
        // at that point (assignRole always runs after the model exists), so
        // it found nothing to match. Re-run now that the role is in place.
        $linking->syncForUser($wali->fresh());

        $this->showWaliModal = false;
        session()->flash('status', "Akun wali untuk keluarga {$this->keluargaUntukWali->nama_kepala_keluarga} berhasil dibuat dan otomatis tertaut.");
    }

    /**
     * Bulk version of "+ Buat Akun" for when there's a backlog of keluarga
     * with no wali yet (typically right after a Santri Import) - clicking
     * "+ Buat Akun" on dozens/hundreds of rows one at a time isn't
     * realistic. Downloads a printable credential list straight away since
     * the No. KK doubles as each new account's login and initial password.
     *
     * The actual PDF is generated by KeluargaController::unduhAkunWaliBaru()
     * via a real GET redirect rather than returned directly from this
     * action - DomPDF's response type isn't recognized by Livewire's
     * automatic file-download detection the way Excel's is (unlike
     * Santri\Import::downloadTemplate(), which can return Excel::download()
     * directly).
     */
    public function bulkBuatAkunWali(WaliAccountService $waliAccounts)
    {
        $dibuat = $waliAccounts->buatAkunMassalUntukSemua();

        if ($dibuat->isEmpty()) {
            session()->flash('status', 'Semua keluarga sudah punya akun wali, tidak ada yang perlu dibuat.');

            return;
        }

        session(['akun_wali_baru_pdf' => $dibuat->map(fn (array $r) => [$r['nama_kepala_keluarga'], $r['no_kk'], (string) $r['jumlah_santri']])->all()]);

        return $this->redirect(route('admin.keluarga.unduh-akun-wali-baru'), navigate: false);
    }

    /**
     * Manual repair action for when wali_santris links go missing without
     * a Santri/User create-or-update event to trigger KeluargaLinkingService
     * on its own - e.g. a raw delete against wali_santris during a manual
     * data reset. Safe to run any time: only adds missing auto-generated
     * links and removes auto-generated ones that no longer match, exactly
     * like the individual sync calls already used elsewhere on this page.
     */
    public function sinkronkanTautan(KeluargaLinkingService $linking): void
    {
        Keluarga::pluck('no_kk')->each(fn (string $noKk) => $linking->syncForNoKk($noKk));

        session()->flash('status', 'Tautan wali-santri berhasil disinkronkan ulang untuk semua keluarga.');
    }

    public function render()
    {
        $keluargas = Keluarga::query()
            ->with(['santris', 'waliUsers'])
            ->withCount(['santris', 'waliUsers'])
            ->when($this->search, fn ($q) => $q->where('no_kk', 'like', "%{$this->search}%")->orWhere('nama_kepala_keluarga', 'like', "%{$this->search}%"))
            ->orderBy('nama_kepala_keluarga')
            ->paginate($this->perPage);

        return view('livewire.admin.keluarga.index', [
            'title' => 'Data Keluarga',
            'keluargas' => $keluargas,
            'keluargaTanpaWaliCount' => Keluarga::whereDoesntHave('waliUsers')->count(),
        ]);
    }
}
