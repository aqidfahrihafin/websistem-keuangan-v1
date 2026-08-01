<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nomor', 'petugas_id', 'device_id', 'lokasi', 'saldo_awal', 'total_masuk',
    'total_keluar', 'saldo_seharusnya', 'uang_fisik_akhir', 'selisih', 'status',
    'catatan_pembukaan', 'catatan_penutupan', 'dibuka_at', 'ditutup_at',
    'diverifikasi_oleh', 'diverifikasi_at',
])]
class SesiKas extends Model
{
    protected $table = 'sesi_kas';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_MENUNGGU_VERIFIKASI = 'menunggu_verifikasi';

    public const STATUS_SESUAI = 'sesuai';

    public const STATUS_SELISIH = 'selisih';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected function casts(): array
    {
        return [
            // MySQL/PDO may return BIGINT foreign keys as numeric strings.
            // These casts keep the strict ownership checks in SesiKasService
            // consistent across MySQL (production) and SQLite (tests/local).
            'petugas_id' => 'integer',
            'device_id' => 'integer',
            'diverifikasi_oleh' => 'integer',
            'saldo_awal' => 'integer',
            'total_masuk' => 'integer',
            'total_keluar' => 'integer',
            'saldo_seharusnya' => 'integer',
            'uang_fisik_akhir' => 'integer',
            'selisih' => 'integer',
            'dibuka_at' => 'datetime',
            'ditutup_at' => 'datetime',
            'diverifikasi_at' => 'datetime',
        ];
    }

    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'petugas_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function diverifikasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diverifikasi_oleh');
    }

    public function mutasi(): HasMany
    {
        return $this->hasMany(MutasiKas::class);
    }
}
