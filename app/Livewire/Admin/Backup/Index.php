<?php

namespace App\Livewire\Admin\Backup;

use App\Livewire\Concerns\WithPerPage;
use App\Services\BackupService;
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

    public ?string $pulihkanNama = null;

    public string $kodeKonfirmasi = '';

    // Rendered inside this component's own view, not the outer layout -
    // session()->flash() lives in layouts/app.blade.php, which Livewire
    // does NOT re-render on a plain wire:click AJAX update (only the
    // component's own root gets morphed), so it would silently never
    // appear after these actions.
    public ?string $pesanSukses = null;

    public ?string $pesanError = null;

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

    public function openPulihkan(string $nama): void
    {
        $this->pulihkanNama = $nama;
        $this->kodeKonfirmasi = '';
        $this->resetValidation();
        $this->showPulihkanModal = true;
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
            $this->pesanSukses = "Database berhasil dipulihkan dari backup {$this->pulihkanNama}. Sebuah backup pengaman dari kondisi sebelumnya telah dibuat otomatis.";
        } catch (Throwable $exception) {
            $this->pesanError = 'Pemulihan gagal: '.$exception->getMessage();
        }
    }

    public function render(BackupService $service)
    {
        // BackupService::daftar() isn't an Eloquent query (it lists .zip
        // files off disk) - paginate() isn't available on a plain array, so
        // the same page/perPage tracking WithPagination already gives every
        // other list here is wrapped around a manual slice instead.
        $search = mb_strtolower(trim($this->search));
        $all = collect($service->daftar())
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
        ]);
    }
}
