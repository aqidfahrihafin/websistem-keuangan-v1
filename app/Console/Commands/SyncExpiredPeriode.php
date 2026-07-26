<?php

namespace App\Console\Commands;

use App\Models\Periode;
use Illuminate\Console\Command;

class SyncExpiredPeriode extends Command
{
    protected $signature = 'periode:sync-expired';

    protected $description = 'Nonaktifkan periode yang tanggal selesainya sudah lewat';

    public function handle(): int
    {
        $count = Periode::syncExpired();

        $this->info("{$count} periode dinonaktifkan karena sudah melewati tanggal selesai.");

        return self::SUCCESS;
    }
}
