<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Services\AppSettingsService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts::app')]
class Aplikasi extends Component
{
    use WithFileUploads;

    public string $nama_aplikasi = '';

    public string $nama_pondok = '';

    public ?string $alamat = null;

    public ?string $telepon = null;

    public ?string $email = null;

    /** Temporary upload, cleared back to null once unggahLogo() succeeds. */
    public $logo = null;

    public ?string $logoUrl = null;

    // Rendered inside this component's own view (not session()->flash(),
    // which only becomes visible after a full page navigation - Livewire
    // actions are AJAX round trips that re-render just this component,
    // never the surrounding layout where a flash banner would live).
    public ?string $statusMessage = null;

    public ?string $errorMessage = null;

    public function mount(AppSettingsService $service): void
    {
        $this->nama_aplikasi = $service->namaAplikasi();
        $this->nama_pondok = $service->namaPondok();
        $this->alamat = $service->alamat();
        $this->telepon = $service->telepon();
        $this->email = $service->email();
        $this->logoUrl = $service->logoUrl();
    }

    /**
     * Explicit validate-then-store step (not auto-upload-on-select), same
     * shape as Santri\Penarikan\Request::unggahSurat() - lets the admin see
     * a live preview and confirm before it's actually saved.
     */
    public function unggahLogo(AppSettingsService $service): void
    {
        $this->statusMessage = null;
        $this->errorMessage = null;

        try {
            $this->validate([
                'logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errorMessage = 'Gagal mengunggah logo. '.($e->errors()['logo'][0] ?? 'Periksa kembali file yang dipilih.');

            throw $e;
        }

        $service->simpanLogo($this->logo);

        $this->logo = null;
        $this->logoUrl = $service->logoUrl();

        $this->statusMessage = 'Logo aplikasi berhasil diperbarui.';
    }

    public function hapusLogo(AppSettingsService $service): void
    {
        $this->statusMessage = null;
        $this->errorMessage = null;

        $service->hapusLogo();
        $this->logoUrl = null;

        $this->statusMessage = 'Logo aplikasi dihapus.';
    }

    public function simpan(AppSettingsService $service): void
    {
        $this->statusMessage = null;
        $this->errorMessage = null;

        try {
            $data = $this->validate([
                'nama_aplikasi' => ['required', 'string', 'max:255'],
                'nama_pondok' => ['required', 'string', 'max:255'],
                'alamat' => ['nullable', 'string', 'max:1000'],
                'telepon' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:255'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errorMessage = 'Gagal menyimpan. Periksa kembali isian yang ditandai di bawah.';

            throw $e;
        }

        $service->save(
            $data['nama_aplikasi'],
            $data['nama_pondok'],
            $data['alamat'],
            $data['telepon'],
            $data['email'],
        );

        $this->statusMessage = 'Pengaturan aplikasi berhasil disimpan.';
    }

    public function render()
    {
        return view('livewire.admin.pengaturan.aplikasi', ['title' => 'Pengaturan Aplikasi']);
    }
}
