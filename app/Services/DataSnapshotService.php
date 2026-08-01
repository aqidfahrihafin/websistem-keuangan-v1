<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Throwable;

class DataSnapshotService
{
    public const SETTING_KEY = 'active_data_snapshot';

    /** @return array{backup_name: string, backup_created_at: ?string, restored_at: string, restored_by: ?string}|null */
    public function current(): ?array
    {
        try {
            if (! Schema::hasTable('settings')) {
                return null;
            }

            $value = Setting::get(self::SETTING_KEY);
            if (! $value) {
                return null;
            }

            $state = json_decode($value, true, flags: JSON_THROW_ON_ERROR);

            return is_array($state) && filled($state['backup_name'] ?? null) ? $state : null;
        } catch (Throwable) {
            // This status must never make the admin shell unusable, including
            // while an old database is being migrated forward after restore.
            return null;
        }
    }

    public function markRestored(string $backupName, ?string $backupCreatedAt, ?string $restoredBy): void
    {
        Setting::put(self::SETTING_KEY, json_encode([
            'backup_name' => $backupName,
            'backup_created_at' => $backupCreatedAt,
            'restored_at' => now()->toIso8601String(),
            'restored_by' => $restoredBy,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * Acknowledges that the currently loaded database has been verified as
     * the production baseline. This changes only the marker, never business
     * data, and remains auditable.
     */
    public function markAsOperationalPrimary(): void
    {
        $previous = $this->current();

        Setting::query()->where('key', self::SETTING_KEY)->delete();

        activity('backup')
            ->causedBy(Auth::user())
            ->withProperties(['snapshot_sebelumnya' => $previous])
            ->log('Menetapkan database aktif sebagai data operasional utama');
    }
}
