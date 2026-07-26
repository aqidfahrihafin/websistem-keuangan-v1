<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama', 'tipe', 'alamat', 'is_active'])]
class Lembaga extends Model
{
    use HasFactory;

    public const TIPE_PONDOK_PUSAT = 'pondok_pusat';

    public const TIPE_SEKOLAH_FORMAL = 'sekolah_formal';

    public const TIPE_LAINNYA = 'lainnya';

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

    public function kamars(): HasMany
    {
        return $this->hasMany(Kamar::class);
    }

    public function jenisTagihans(): HasMany
    {
        return $this->hasMany(JenisTagihan::class);
    }
}
