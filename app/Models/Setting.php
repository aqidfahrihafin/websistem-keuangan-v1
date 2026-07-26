<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    use LogsActivity;

    protected function casts(): array
    {
        return [
            'value' => 'encrypted',
        ];
    }

    /**
     * Logs only *which* key changed, never the value itself - `value`
     * covers things like the Midtrans server key and PIN-adjacent config,
     * and this table's own `encrypted` cast exists specifically so those
     * never sit in the database as plaintext. Logging old/new `value`
     * here would defeat that by writing the decrypted value straight into
     * activity_log.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['key'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->first()?->value ?? $default;
    }

    public static function put(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
