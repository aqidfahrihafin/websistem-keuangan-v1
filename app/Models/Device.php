<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['kode_device', 'nama', 'lokasi', 'tipe', 'unit_usaha_id', 'status', 'last_seen_at', 'petugas_jaga_id', 'petugas_jaga_sejak', 'sesi_kas_aktif_id'])]
class Device extends Model implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens, HasFactory;

    public const TIPE_KIOSK_SALDO = 'kiosk_saldo';

    public const TIPE_KIOSK_PENARIKAN = 'kiosk_penarikan';

    public const TIPE_KANTIN = 'kantin';

    protected function casts(): array
    {
        return [
            'unit_usaha_id' => 'integer',
            'petugas_jaga_id' => 'integer',
            'sesi_kas_aktif_id' => 'integer',
            'last_seen_at' => 'datetime',
            'petugas_jaga_sejak' => 'datetime',
        ];
    }

    public function penarikanRequests(): HasMany
    {
        return $this->hasMany(PenarikanRequest::class);
    }

    public function petugasJaga(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_jaga_id');
    }

    public function petugasTerdaftar(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'device_petugas')
            ->withPivot(['aktif', 'ditugaskan_oleh', 'ditugaskan_at'])
            ->withTimestamps();
    }

    public function sesiKasAktif(): BelongsTo
    {
        return $this->belongsTo(SesiKas::class, 'sesi_kas_aktif_id');
    }

    public function sesiKas(): HasMany
    {
        return $this->hasMany(SesiKas::class);
    }

    /** Only meaningful for tipe=kantin - which kantin this kiosk is installed at. */
    public function unitUsaha(): BelongsTo
    {
        return $this->belongsTo(UnitUsaha::class);
    }
}
