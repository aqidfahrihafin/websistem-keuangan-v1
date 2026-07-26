<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama', 'deskripsi', 'nominal_default', 'periode', 'lembaga_id', 'is_active', 'berlaku_diskon', 'bisa_dicicil'])]
class JenisTagihan extends Model
{
    use HasFactory;

    public const PERIODE_BULANAN = 'bulanan';

    public const PERIODE_TAHUNAN = 'tahunan';

    public const PERIODE_SEKALI = 'sekali';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'berlaku_diskon' => 'boolean',
            'bisa_dicicil' => 'boolean',
        ];
    }

    public function lembaga(): BelongsTo
    {
        return $this->belongsTo(Lembaga::class);
    }

    public function tagihans(): HasMany
    {
        return $this->hasMany(Tagihan::class);
    }
}
