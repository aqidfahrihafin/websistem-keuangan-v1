<?php

namespace App\Models;

use App\Exceptions\ImmutableLedgerException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * The kantin/unit-usaha side of a payment - a separate ledger from
 * `transaksis` (which is strictly santri-scoped) rather than overloading
 * that table, since a unit usaha isn't a santri. Immutable, same
 * update/delete-blocking convention as Transaksi.
 */
#[Fillable([
    'unit_usaha_id', 'arah', 'nominal', 'saldo_sebelum', 'saldo_sesudah',
    'jenis', 'transaksi_id', 'unit_usaha_penarikan_id', 'dicatat_oleh',
])]
class UnitUsahaTransaksi extends Model
{
    public const UPDATED_AT = null;

    public const ARAH_DEBIT = 'debit';

    public const ARAH_KREDIT = 'kredit';

    public const JENIS_PEMBAYARAN_MASUK = 'pembayaran_masuk';

    public const JENIS_PENARIKAN_KELUAR = 'penarikan_keluar';

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function () {
            throw new ImmutableLedgerException('Ledger unit usaha tidak boleh diubah.');
        });

        static::deleting(function () {
            throw new ImmutableLedgerException('Ledger unit usaha tidak boleh dihapus.');
        });
    }

    public function unitUsaha(): BelongsTo
    {
        return $this->belongsTo(UnitUsaha::class);
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function unitUsahaPenarikan(): BelongsTo
    {
        return $this->belongsTo(UnitUsahaPenarikan::class);
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }
}
