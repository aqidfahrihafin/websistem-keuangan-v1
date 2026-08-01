<?php

namespace App\Models;

use App\Exceptions\ImmutableLedgerException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid', 'rekening_tabungan_id', 'jenis', 'kanal', 'arah', 'nominal',
    'saldo_sebelum', 'saldo_sesudah', 'status', 'transfer_uuid',
    'referensi_type', 'referensi_id', 'diproses_oleh', 'device_id',
    'sesi_kas_id', 'idempotency_key', 'catatan', 'metadata',
])]
class TransaksiTabungan extends Model
{
    public const UPDATED_AT = null;

    public const JENIS_SETORAN_TUNAI = 'setoran_tunai';

    public const JENIS_SETORAN_DARI_SALDO = 'setoran_dari_saldo';

    public const JENIS_SETORAN_MIDTRANS = 'setoran_midtrans';

    public const JENIS_KOREKSI_MASUK = 'koreksi_masuk';

    public const JENIS_KOREKSI_KELUAR = 'koreksi_keluar';

    public const KANAL_PETUGAS = 'petugas';

    public const KANAL_KIOS = 'kios';

    public const KANAL_WALI = 'aplikasi_wali';

    public const KANAL_MIDTRANS = 'midtrans';

    public const ARAH_KREDIT = 'kredit';

    public const ARAH_DEBIT = 'debit';

    protected function casts(): array
    {
        return [
            'nominal' => 'integer',
            'saldo_sebelum' => 'integer',
            'saldo_sesudah' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (TransaksiTabungan $transaksi) => $transaksi->uuid ??= (string) Str::uuid());
        static::updating(fn () => throw new ImmutableLedgerException('Ledger tabungan tidak boleh diubah.'));
        static::deleting(fn () => throw new ImmutableLedgerException('Ledger tabungan tidak boleh dihapus.'));
    }

    public function rekening(): BelongsTo
    {
        return $this->belongsTo(RekeningTabungan::class, 'rekening_tabungan_id');
    }

    public function referensi(): MorphTo
    {
        return $this->morphTo();
    }

    public function sesiKas(): BelongsTo
    {
        return $this->belongsTo(SesiKas::class);
    }
}
