<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['kode_device', 'nama', 'lokasi', 'tipe', 'unit_usaha_id', 'status', 'last_seen_at', 'petugas_jaga_id', 'petugas_jaga_sejak'])]
class Device extends Model implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens, HasFactory;

    public const TIPE_KIOSK_SALDO = 'kiosk_saldo';

    public const TIPE_KIOSK_PENARIKAN = 'kiosk_penarikan';

    public const TIPE_KANTIN = 'kantin';

    protected function casts(): array
    {
        return [
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

    /** Only meaningful for tipe=kantin - which kantin this kiosk is installed at. */
    public function unitUsaha(): BelongsTo
    {
        return $this->belongsTo(UnitUsaha::class);
    }
}
