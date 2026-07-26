<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nama', 'jam_mulai', 'jam_selesai', 'limit_harian', 'applies_lembaga_id', 'is_active', 'effective_from'])]
class KebijakanPenarikan extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'effective_from' => 'date',
        ];
    }

    public function appliesLembaga(): BelongsTo
    {
        return $this->belongsTo(Lembaga::class, 'applies_lembaga_id');
    }

    /**
     * @param  Builder<KebijakanPenarikan>  $query
     * @return Builder<KebijakanPenarikan>
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('effective_from', '<=', now()->toDateString());
    }
}
