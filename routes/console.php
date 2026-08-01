<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\MaintenanceModeService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('operations:maintenance-off', function (MaintenanceModeService $maintenance) {
    $maintenance->deactivate();
    $this->info('Mode maintenance operasional dinonaktifkan.');
})->purpose('Jalur pemulihan darurat jika sesi admin tidak dapat digunakan');

$maintenanceActive = app(MaintenanceModeService::class)->active();

if (! $maintenanceActive) {
    Schedule::command('periode:sync-expired')->daily()->withoutOverlapping();
    Schedule::command('tagihan:ingatkan-jatuh-tempo')->daily()->withoutOverlapping();
}

// Expired tokens are rejected by Sanctum at request time, but pruning them
// keeps the personal_access_tokens table bounded over years of mobile use.
if (! $maintenanceActive) {
    Schedule::command('sanctum:prune-expired --hours=24')
        ->dailyAt('01:30')
        ->withoutOverlapping();
}

if (! $maintenanceActive && config('operations.automatic_backup_enabled')) {
    Schedule::command('backup:versioned')
        ->dailyAt(config('operations.automatic_backup_time'))
        ->withoutOverlapping(180);

    Schedule::command('backup:clean --disable-notifications')
        ->dailyAt(config('operations.automatic_backup_cleanup_time'))
        ->withoutOverlapping(180);
}
