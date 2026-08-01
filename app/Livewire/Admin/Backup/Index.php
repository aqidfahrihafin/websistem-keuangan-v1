<?php

namespace App\Livewire\Admin\Backup;

use App\Livewire\Concerns\WithPerPage;
use App\Services\BackupService;
use App\Services\BackupSettingsService;
use App\Services\BackupHealthService;
use App\Services\DataSnapshotService;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;
use Throwable;

#[Layout('layouts::app')]
class Index extends Component
{
    use WithPagination, WithPerPage;

    public string $search = '';

    public bool $showPulihkanModal = false;

    public bool $showKonfigurasiModal = false;

    public string $backupMode = BackupSettingsService::MODE_AUTO;

    public string $backupBinaryPath = '';

    public ?string $hasilTesKonfigurasi = null;

    public ?string $pulihkanNama = null;

    public ?array $pulihkanKompatibilitas = null;

    public string $kodeKonfirmasi = '';

    // Rendered inside this component's own view, not the outer layout -
    // session()->flash() lives in layouts/app.blade.php, which Livewire
    // does NOT re-render on a plain wire:click AJAX update (only the
    // component's own root gets morphed), so it would silently never
    // appear after these actions.
    public ?string $pesanSukses = null;

    public ?string $pesanError = null;

    public function mount(BackupSettingsService $settings): void
    {
        $this->backupMode = $settings->mode();
        $this->backupBinaryPath = $settings->binaryPath() ?? '';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function buat(BackupService $service): void
    {
        $this->pesanSukses = null;
        $this->pesanError = null;

        try {
            $service->buat();
            $this->pesanSukses = 'Backup baru berhasil dibuat.';
        } catch (Throwable $exception) {
            $this->pesanError = $exception->getMessage();
        }
    }

    public function hapus(string $nama, BackupService $service): void
    {
        $this->pesanSukses = null;
        $this->pesanError = null;

        try {
            $service->hapus($nama);
            $this->pesanSukses = "Backup {$nama} berhasil dihapus.";
        } catch (Throwable $exception) {
            $this->pesanError = $exception->getMessage();
        }
    }

    public function openPulihkan(string $nama, BackupService $service): void
    {
        $this->pulihkanNama = $nama;
        $this->pulihkanKompatibilitas = $service->inspeksi($nama);
        $this->kodeKonfirmasi = '';
        $this->resetValidation();
        $this->showPulihkanModal = true;
    }

    public function openKonfigurasi(BackupSettingsService $settings): void
    {
        $this->backupMode = $settings->mode();
        $this->backupBinaryPath = $settings->binaryPath() ?? '';
        $this->hasilTesKonfigurasi = null;
        $this->resetValidation();
        $this->showKonfigurasiModal = true;
    }

    public function ujiKonfigurasi(BackupService $service): void
    {
        $this->validateKonfigurasi();
        $this->hasilTesKonfigurasi = null;

        try {
            $hasil = $service->ujiKonfigurasi($this->backupMode, $this->backupBinaryPath);
            $this->hasilTesKonfigurasi = $hasil['pesan'];
        } catch (Throwable $exception) {
            $this->addError('backupBinaryPath', $exception->getMessage());
        }
    }

    public function simpanKonfigurasi(BackupService $service, BackupSettingsService $settings): void
    {
        $this->validateKonfigurasi();
        $this->pesanSukses = null;
        $this->pesanError = null;

        try {
            $hasil = $service->ujiKonfigurasi($this->backupMode, $this->backupBinaryPath);
            $settings->save($this->backupMode, $this->backupBinaryPath);
            $this->showKonfigurasiModal = false;
            $this->hasilTesKonfigurasi = null;
            $this->pesanSukses = 'Konfigurasi backup berhasil disimpan. '.$hasil['pesan'];
        } catch (Throwable $exception) {
            $this->addError('backupBinaryPath', $exception->getMessage());
        }
    }

    private function validateKonfigurasi(): void
    {
        $this->validate([
            'backupMode' => ['required', 'in:auto,cli,pdo'],
            'backupBinaryPath' => ['nullable', 'string', 'max:500'],
        ], [
            'backupMode.in' => 'Mode backup tidak valid.',
            'backupBinaryPath.max' => 'Path binary maksimal 500 karakter.',
        ]);
    }

    public function pulihkan(BackupService $service): void
    {
        if ($this->kodeKonfirmasi !== BackupService::KODE_KONFIRMASI_PULIHKAN) {
            $this->addError('kodeKonfirmasi', 'Ketik "'.BackupService::KODE_KONFIRMASI_PULIHKAN.'" persis untuk mengonfirmasi.');

            return;
        }

        $this->pesanSukses = null;
        $this->pesanError = null;

        try {
            $service->pulihkan($this->pulihkanNama, $this->kodeKonfirmasi);
            $this->showPulihkanModal = false;
            $this->pesanSukses = "Database berhasil dipulihkan dari backup {$this->pulihkanNama}, schema diperbarui ke versi aplikasi saat ini, dan pemeriksaan integritas berhasil. Sebuah backup pengaman dari kondisi sebelumnya telah dibuat otomatis.";
        } catch (Throwable $exception) {
            $this->pesanError = 'Pemulihan gagal: '.$exception->getMessage();
        }
    }

    public function jadikanDataUtama(DataSnapshotService $snapshot): void
    {
        $snapshot->markAsOperationalPrimary();
        $this->pesanError = null;
        $this->pesanSukses = 'Database aktif telah ditetapkan sebagai data operasional utama. Penanda hasil restore sudah dihapus.';
    }

    public function render(BackupService $service, DataSnapshotService $snapshot, BackupHealthService $health)
    {
        // BackupService::daftar() isn't an Eloquent query (it lists .zip
        // files off disk) - paginate() isn't available on a plain array, so
        // the same page/perPage tracking WithPagination already gives every
        // other list here is wrapped around a manual slice instead.
        $search = mb_strtolower(trim($this->search));
        $allBackups = collect($service->daftar());
        $all = $allBackups
            ->when(
                $search !== '',
                fn ($backups) => $backups->filter(
                    fn (array $backup) => str_contains(
                        mb_strtolower((string) ($backup['nama'] ?? '')),
                        $search,
                    ),
                ),
            )
            ->values();
        $page = $this->getPage();

        $backups = new LengthAwarePaginator(
            $all->forPage($page, $this->perPage)->values(),
            $all->count(),
            $this->perPage,
            $page,
            ['path' => request()->url(), 'pageName' => 'page'],
        );

        return view('livewire.admin.backup.index', [
            'title' => 'Backup & Restore',
            'backups' => $backups,
            'kesiapan' => $service->kesiapan(),
            'health' => $health->status($allBackups->all()),
            'snapshotAktif' => $snapshot->current(),
        ]);
    }
}
