<?php

namespace App\Livewire\Admin\Pengaturan;

use App\Services\BackupService;
use App\Services\MaintenanceModeService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::app')]
class Maintenance extends Component
{
    public string $message = 'Sistem sedang dalam pemeliharaan untuk meningkatkan keamanan dan layanan.';
    public ?string $expectedEndAt = null;
    public string $confirmation = '';
    public ?string $successMessage = null;
    public ?string $errorMessage = null;

    public function mount(MaintenanceModeService $maintenance): void
    {
        $status = $maintenance->status();
        $this->message = $status['message'];
        $this->expectedEndAt = $status['expected_end_at']?->format('Y-m-d\TH:i');
    }

    public function activate(MaintenanceModeService $maintenance, BackupService $backup): void
    {
        $this->reset(['successMessage', 'errorMessage']);
        $data = $this->validate([
            'message' => ['required', 'string', 'min:10', 'max:500'],
            'expectedEndAt' => ['nullable', 'date', 'after:now'],
            'confirmation' => ['required', 'in:MAINTENANCE'],
        ], ['confirmation.in' => 'Ketik MAINTENANCE dengan tepat untuk melanjutkan.']);

        if ($maintenance->active()) {
            $this->errorMessage = 'Mode maintenance sudah aktif.';
            return;
        }

        try {
            // Capture the last known-good state before writes are blocked.
            $backup->buat();
            $maintenance->activate(
                $data['message'],
                filled($data['expectedEndAt']) ? Carbon::parse($data['expectedEndAt']) : null,
                auth()->user(),
            );
            try {
                Artisan::call('queue:restart');
            } catch (\Throwable $queueError) {
                report($queueError);
            }
            $this->confirmation = '';
            $this->successMessage = 'Maintenance aktif. Backup pengaman berhasil dibuat dan worker queue diminta berhenti setelah pekerjaan aktif selesai.';
        } catch (\Throwable $error) {
            report($error);
            $this->errorMessage = 'Maintenance tidak diaktifkan karena backup pengaman gagal: '.$error->getMessage();
        }
    }

    public function deactivate(MaintenanceModeService $maintenance): void
    {
        $this->reset(['successMessage', 'errorMessage']);
        $maintenance->deactivate(auth()->user());
        session()->forget('maintenance.admin_recovery');
        $this->confirmation = '';
        $this->successMessage = 'Maintenance dinonaktifkan. Akses pengguna dan pemrosesan terjadwal kembali dibuka.';
    }

    public function render(MaintenanceModeService $maintenance)
    {
        $status = $maintenance->status();
        $pendingJobs = DB::getSchemaBuilder()->hasTable('jobs') ? DB::table('jobs')->count() : 0;

        return view('livewire.admin.pengaturan.maintenance', [
            'title' => 'Maintenance Sistem',
            'status' => $status,
            'pendingJobs' => $pendingJobs,
        ]);
    }
}
