<?php

namespace App\Models;

use App\Exceptions\ImmutableLedgerException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid', 'sesi_kas_id', 'arah', 'kategori', 'nominal', 'referensi_type',
    'referensi_id', 'diproses_oleh', 'idempotency_key', 'keterangan',
])]
class MutasiKas extends Model
{
    public const UPDATED_AT = null;

    public const ARAH_MASUK = 'masuk';

    public const ARAH_KELUAR = 'keluar';

    public const KATEGORI_SETORAN_TABUNGAN = 'setoran_tabungan';

    protected $table = 'mutasi_kas';

    protected static function booted(): void
    {
        static::creating(fn (MutasiKas $mutasi) => $mutasi->uuid ??= (string) Str::uuid());
        static::updating(fn () => throw new ImmutableLedgerException('Mutasi kas tidak boleh diubah.'));
        static::deleting(fn () => throw new ImmutableLedgerException('Mutasi kas tidak boleh dihapus.'));
    }

    public function sesiKas(): BelongsTo
    {
        return $this->belongsTo(SesiKas::class);
    }

    public function referensi(): MorphTo
    {
        return $this->morphTo();
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }
}
