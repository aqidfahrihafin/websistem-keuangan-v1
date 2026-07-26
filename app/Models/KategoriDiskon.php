<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama', 'persentase', 'is_active'])]
class KategoriDiskon extends Model
{
    use HasFactory;

    /**
     * Reserved kode for categories the system auto-assigns - looked up by
     * kode (never by name) so admins can freely rename the display label or
     * change the persentase without breaking the auto-assignment logic.
     */
    public const KODE_BERSAUDARA = 'BERSAUDARA';

    public const KODE_SANTRI_BARU = 'SANTRI_BARU';

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function santris(): HasMany
    {
        return $this->hasMany(Santri::class);
    }
}
