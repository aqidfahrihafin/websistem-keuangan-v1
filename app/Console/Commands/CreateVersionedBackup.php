<?php

namespace App\Console\Commands;

use App\Services\BackupService;
use Illuminate\Console\Command;
use Throwable;

class CreateVersionedBackup extends Command
{
    protected $signature = 'backup:versioned';

    protected $description = 'Create a database backup with schema/version manifest and checksum';

    public function handle(BackupService $service): int
    {
        try {
            $service->buat();
            $this->info('Backup berversi berhasil dibuat.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Backup berversi gagal: '.$exception->getMessage());

            return self::FAILURE;
        }
    }
}
