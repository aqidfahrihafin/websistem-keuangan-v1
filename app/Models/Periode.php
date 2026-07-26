<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

#[Fillable(['label', 'tanggal_mulai', 'tanggal_selesai', 'is_active'])]
class Periode extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tanggal_mulai' => 'date',
            'tanggal_selesai' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Only one periode is ever active at a time - activating this one
     * atomically deactivates every other periode.
     */
    public function activate(): void
    {
        DB::transaction(function () {
            self::query()->where('id', '!=', $this->id)->update(['is_active' => false]);
            $this->update(['is_active' => true]);
        });
    }

    /**
     * A periode is only considered expired the day AFTER its tanggal_selesai
     * (i.e. tanggal_selesai itself still counts as valid), and periode
     * without a tanggal_selesai never expire on their own.
     */
    public function isExpired(): bool
    {
        return $this->tanggal_selesai !== null && $this->tanggal_selesai->lt(today());
    }

    /**
     * Deactivates every active periode whose tanggal_selesai has passed.
     * Called both lazily (whenever the periode list/active periode is read)
     * and from a daily scheduled command, so it stays correct whether or not
     * anyone opens the Periode page that day.
     */
    public static function syncExpired(): int
    {
        return static::query()
            ->where('is_active', true)
            ->whereNotNull('tanggal_selesai')
            ->whereDate('tanggal_selesai', '<', today())
            ->update(['is_active' => false]);
    }
}
