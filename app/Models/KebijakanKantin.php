<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nama', 'limit_harian', 'applies_lembaga_id', 'is_active', 'effective_from'])]
class KebijakanKantin extends Model
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
     * @param  Builder<KebijakanKantin>  $query
     * @return Builder<KebijakanKantin>
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('effective_from', '<=', now()->toDateString());
    }
}
