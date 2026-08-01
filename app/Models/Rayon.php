<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['kode', 'nama', 'alamat', 'penanggung_jawab', 'is_active'])]
class Rayon extends Model
{
    use SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function kamars(): HasMany { return $this->hasMany(Kamar::class); }
    public function santris(): HasMany { return $this->hasMany(Santri::class); }

    public function pengelolas(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'unit_user')
            ->withPivot(['akses', 'aktif', 'ditugaskan_oleh', 'ditugaskan_at'])
            ->withTimestamps();
    }
}
