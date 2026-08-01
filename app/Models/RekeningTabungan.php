<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['santri_id', 'saldo', 'status', 'dibuka_at'])]
class RekeningTabungan extends Model
{
    public const STATUS_AKTIF = 'aktif';

    public const STATUS_DIBEKUKAN = 'dibekukan';

    protected function casts(): array
    {
        return ['saldo' => 'integer', 'dibuka_at' => 'datetime'];
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function transaksi(): HasMany
    {
        return $this->hasMany(TransaksiTabungan::class);
    }
}
