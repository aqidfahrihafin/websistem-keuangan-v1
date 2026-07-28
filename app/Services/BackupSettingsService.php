<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class BackupSettingsService
{
    public const MODE_AUTO = 'auto';

    public const MODE_CLI = 'cli';

    public const MODE_PDO = 'pdo';

    public function mode(): string
    {
        $mode = Setting::get('backup_mode', self::MODE_AUTO);

        return in_array($mode, $this->modes(), true) ? $mode : self::MODE_AUTO;
    }

    public function binaryPath(): ?string
    {
        $path = trim((string) Setting::get('backup_binary_path'));

        return $path !== '' ? rtrim($path, '/\\') : null;
    }

    /** @return array<int, string> */
    public function modes(): array
    {
        return [self::MODE_AUTO, self::MODE_CLI, self::MODE_PDO];
    }

    public function save(string $mode, ?string $binaryPath): void
    {
        if (! in_array($mode, $this->modes(), true)) {
            throw new InvalidArgumentException('Mode backup tidak valid.');
        }

        $binaryPath = trim((string) $binaryPath);

        Setting::put('backup_mode', $mode);
        Setting::put('backup_binary_path', $binaryPath !== '' ? rtrim($binaryPath, '/\\') : null);
        Cache::forget('backup:kesiapan');
    }
}
