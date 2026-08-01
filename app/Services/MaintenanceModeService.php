<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class MaintenanceModeService
{
    private const CACHE_KEY = 'system.maintenance.status';

    public function status(): array
    {
        return Cache::remember(self::CACHE_KEY, 5, function (): array {
            if (! Schema::hasTable('settings')) {
                return $this->defaults();
            }

            $enabled = Setting::get('maintenance_enabled', '0') === '1';

            return [
                'enabled' => $enabled,
                'message' => Setting::get('maintenance_message', 'Sistem sedang dalam pemeliharaan untuk meningkatkan keamanan dan layanan.'),
                'started_at' => $this->parseDate(Setting::get('maintenance_started_at')),
                'expected_end_at' => $this->parseDate(Setting::get('maintenance_expected_end_at')),
                'activated_by' => Setting::get('maintenance_activated_by'),
            ];
        });
    }

    public function active(): bool
    {
        return $this->status()['enabled'];
    }

    public function activate(string $message, ?Carbon $expectedEndAt, User $actor): void
    {
        Setting::put('maintenance_message', trim($message));
        Setting::put('maintenance_started_at', now()->toIso8601String());
        Setting::put('maintenance_expected_end_at', $expectedEndAt?->toIso8601String());
        Setting::put('maintenance_activated_by', $actor->name);
        Setting::put('maintenance_enabled', '1');
        Cache::forget(self::CACHE_KEY);

        activity('maintenance')
            ->causedBy($actor)
            ->withProperties([
                'expected_end_at' => $expectedEndAt?->toIso8601String(),
                'message' => trim($message),
            ])
            ->log('Mode maintenance diaktifkan');
    }

    public function deactivate(?User $actor = null): void
    {
        $previous = $this->status();
        Setting::put('maintenance_enabled', '0');
        Setting::put('maintenance_expected_end_at', null);
        Cache::forget(self::CACHE_KEY);

        $activity = activity('maintenance')
            ->withProperties([
                'started_at' => $previous['started_at']?->toIso8601String(),
                'source' => $actor ? 'admin_panel' : 'console',
            ]);
        if ($actor) {
            $activity->causedBy($actor);
        }
        $activity->log('Mode maintenance dinonaktifkan');
    }

    public function publicStatus(): array
    {
        $status = $this->status();

        return [
            'maintenance' => $status['enabled'],
            'message' => $status['message'],
            'started_at' => $status['started_at']?->toIso8601String(),
            'expected_end_at' => $status['expected_end_at']?->toIso8601String(),
        ];
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function defaults(): array
    {
        return [
            'enabled' => false,
            'message' => 'Sistem sedang dalam pemeliharaan untuk meningkatkan keamanan dan layanan.',
            'started_at' => null,
            'expected_end_at' => null,
            'activated_by' => null,
        ];
    }
}
