<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'lembaga_id', 'kode', 'nama', 'gedung', 'lantai', 'kapasitas',
    'jenis_kelamin', 'is_active',
])]
class Kamar extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return [
            'lantai' => 'integer',
            'kapasitas' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function lembaga(): BelongsTo
    {
        return $this->belongsTo(Lembaga::class);
    }

    public function santris(): HasMany
    {
        return $this->hasMany(Santri::class);
    }

    public function riwayatPenempatan(): HasMany
    {
        return $this->hasMany(RiwayatKamarSantri::class);
    }
}
