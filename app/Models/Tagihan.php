<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'santri_id', 'jenis_tagihan_id', 'periode_label', 'nominal', 'nominal_terbayar',
    'status', 'jatuh_tempo', 'generated_by', 'generated_batch_id',
    'nominal_sebelum_diskon', 'diskon_persen', 'kategori_diskon_id',
    'alasan_pembatalan', 'dibatalkan_oleh', 'dibatalkan_at', 'reminder_terkirim_at',
])]
class Tagihan extends Model
{
    use HasFactory;

    public const STATUS_BELUM_LUNAS = 'belum_lunas';

    public const STATUS_SEBAGIAN = 'sebagian';

    public const STATUS_LUNAS = 'lunas';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected function casts(): array
    {
        return [
            'jatuh_tempo' => 'date',
            'dibatalkan_at' => 'datetime',
            'reminder_terkirim_at' => 'datetime',
        ];
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function jenisTagihan(): BelongsTo
    {
        return $this->belongsTo(JenisTagihan::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function dibatalkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }

    public function kategoriDiskon(): BelongsTo
    {
        return $this->belongsTo(KategoriDiskon::class);
    }

    public function pembayarans(): HasMany
    {
        return $this->hasMany(TagihanPembayaran::class);
    }

    public function sisa(): int
    {
        return max(0, $this->nominal - $this->nominal_terbayar);
    }
}
