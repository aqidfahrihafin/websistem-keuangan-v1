<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'keluarga_id', 'user_id', 'lembaga_id', 'kamar_id', 'nis', 'nik', 'nama', 'tempat_lahir',
    'tanggal_lahir', 'jenis_kelamin', 'alamat', 'status', 'tanggal_masuk', 'foto_path',
    'kategori_diskon_id', 'kategori_diskon_auto',
])]
class Santri extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public const STATUS_BARU = 'baru';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_NONAKTIF = 'nonaktif';

    public const STATUS_LULUS = 'lulus';

    public const STATUS_KELUAR = 'keluar';

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
            'tanggal_masuk' => 'date',
            'kategori_diskon_auto' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama', 'nis', 'nik', 'status', 'lembaga_id', 'kamar_id', 'keluarga_id', 'kategori_diskon_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function keluarga(): BelongsTo
    {
        return $this->belongsTo(Keluarga::class);
    }

    public function kategoriDiskon(): BelongsTo
    {
        return $this->belongsTo(KategoriDiskon::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lembaga(): BelongsTo
    {
        return $this->belongsTo(Lembaga::class);
    }

    public function kamar(): BelongsTo
    {
        return $this->belongsTo(Kamar::class);
    }

    public function riwayatKamar(): HasMany
    {
        return $this->hasMany(RiwayatKamarSantri::class);
    }

    public function kartuSantris(): HasMany
    {
        return $this->hasMany(KartuSantri::class);
    }

    public function kartuAktif(): HasOne
    {
        return $this->hasOne(KartuSantri::class)->where('status', KartuSantri::STATUS_AKTIF)->latestOfMany();
    }

    public function waliSantris(): HasMany
    {
        return $this->hasMany(WaliSantri::class);
    }

    /**
     * @return BelongsToMany<User, Santri>
     */
    public function walis(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wali_santris')
            ->withPivot(['hubungan', 'is_auto_generated', 'is_primary'])
            ->withTimestamps();
    }

    public function tagihans(): HasMany
    {
        return $this->hasMany(Tagihan::class);
    }

    public function saldo(): HasOne
    {
        return $this->hasOne(SaldoSantri::class);
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(Transaksi::class);
    }

    public function penarikanRequests(): HasMany
    {
        return $this->hasMany(PenarikanRequest::class);
    }

    public function topupWalis(): HasMany
    {
        return $this->hasMany(TopupWali::class);
    }
}
