<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'kode', 'nama', 'tipe', 'saldo_unit', 'pengelola_user_id', 'status',
    'bank_nama', 'bank_no_rekening', 'bank_atas_nama',
])]
class UnitUsaha extends Model
{
    use HasFactory;

    public const TIPE_KANTIN = 'kantin';

    public const TIPE_KOPERASI = 'koperasi';

    public const TIPE_LAINNYA = 'lainnya';

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_NONAKTIF = 'nonaktif';

    public function pengelola(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pengelola_user_id');
    }

    public function transaksis(): HasMany
    {
        return $this->hasMany(UnitUsahaTransaksi::class);
    }

    public function penarikans(): HasMany
    {
        return $this->hasMany(UnitUsahaPenarikan::class);
    }

    public function rekeningPerubahans(): HasMany
    {
        return $this->hasMany(UnitUsahaRekeningPerubahan::class);
    }

    public function rekeningPerubahanMenunggu(): ?UnitUsahaRekeningPerubahan
    {
        return $this->rekeningPerubahans()
            ->where('status', UnitUsahaRekeningPerubahan::STATUS_MENUNGGU)
            ->latest()
            ->first();
    }
}
