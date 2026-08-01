<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class BackupHealthService
{
    public function recordAttempt(): void
    {
        Setting::put('backup_last_attempt_at', now()->toIso8601String());
    }

    public function recordSuccess(string $name): void
    {
        Setting::put('backup_last_success_at', now()->toIso8601String());
        Setting::put('backup_last_success_name', $name);
        Setting::put('backup_last_error', null);
    }

    public function recordFailure(Throwable $error): void
    {
        Setting::put('backup_last_failure_at', now()->toIso8601String());
        Setting::put('backup_last_error', str($error->getMessage())->limit(500)->toString());
    }

    public function syncOffsite(string $localPath): void
    {
        if (! config('operations.backup_offsite_enabled')) {
            return;
        }

        $diskName = (string) config('operations.backup_offsite_disk');
        if ($diskName === '' || $diskName === BackupService::DISK) {
            throw new RuntimeException('Disk off-site harus berbeda dari disk backup lokal.');
        }

        $stream = Storage::disk(BackupService::DISK)->readStream($localPath);
        if (! is_resource($stream)) {
            throw new RuntimeException('Backup lokal tidak dapat dibaca untuk replikasi off-site.');
        }

        $remotePath = trim((string) config('operations.backup_offsite_prefix'), '/').'/'.basename($localPath);
        try {
            if (! Storage::disk($diskName)->writeStream($remotePath, $stream)) {
                throw new RuntimeException('Penyimpanan off-site menolak berkas backup.');
            }
        } finally {
            fclose($stream);
        }

        Setting::put('backup_offsite_last_success_at', now()->toIso8601String());
        Setting::put('backup_offsite_last_success_name', basename($localPath));
        Setting::put('backup_offsite_last_error', null);
    }

    public function recordOffsiteFailure(Throwable $error): void
    {
        Setting::put('backup_offsite_last_failure_at', now()->toIso8601String());
        Setting::put('backup_offsite_last_error', str($error->getMessage())->limit(500)->toString());
    }

    public function status(array $backups): array
    {
        $latest = $backups[0] ?? null;
        $lastSuccess = $this->date(Setting::get('backup_last_success_at')) ?? ($latest['dibuat_at'] ?? null);
        $age = $lastSuccess?->diffInHours(now());
        $warning = (int) config('operations.backup_warning_after_hours', 26);
        $critical = (int) config('operations.backup_critical_after_hours', 48);
        $level = ! $lastSuccess || $age >= $critical ? 'critical' : ($age >= $warning ? 'warning' : 'healthy');

        return [
            'level' => $level,
            'label' => match ($level) { 'healthy' => 'Sehat', 'warning' => 'Perlu perhatian', default => 'Kritis' },
            'automatic_enabled' => (bool) config('operations.automatic_backup_enabled'),
            'automatic_time' => config('operations.automatic_backup_time'),
            'last_attempt_at' => $this->date(Setting::get('backup_last_attempt_at')),
            'last_success_at' => $lastSuccess,
            'last_success_name' => Setting::get('backup_last_success_name') ?? ($latest['nama'] ?? null),
            'last_failure_at' => $this->date(Setting::get('backup_last_failure_at')),
            'last_error' => Setting::get('backup_last_error'),
            'age_hours' => $age,
            'offsite_enabled' => (bool) config('operations.backup_offsite_enabled'),
            'offsite_disk' => config('operations.backup_offsite_disk'),
            'offsite_last_success_at' => $this->date(Setting::get('backup_offsite_last_success_at')),
            'offsite_last_success_name' => Setting::get('backup_offsite_last_success_name'),
            'offsite_last_error' => Setting::get('backup_offsite_last_error'),
        ];
    }

    private function date(?string $value): ?Carbon
    {
        try {
            return filled($value) ? Carbon::parse($value) : null;
        } catch (Throwable) {
            return null;
        }
    }
}
