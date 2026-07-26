<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'no_kk', 'nama_kepala_keluarga', 'nik_kepala_keluarga',
    'tempat_lahir_kepala_keluarga', 'tanggal_lahir_kepala_keluarga', 'alamat',
])]
class Keluarga extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'tanggal_lahir_kepala_keluarga' => 'date',
        ];
    }

    public function santris(): HasMany
    {
        return $this->hasMany(Santri::class);
    }

    /**
     * Not a real foreign key - users.no_kk and keluargas.no_kk are matched
     * by value (see KeluargaLinkingService), same as the rest of the app.
     * Scoped to role=wali since no other role legitimately sets no_kk.
     */
    public function waliUsers(): HasMany
    {
        return $this->hasMany(User::class, 'no_kk', 'no_kk')
            ->whereHas('roles', fn ($q) => $q->where('name', 'wali'));
    }
}
