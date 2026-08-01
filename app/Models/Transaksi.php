<?php

namespace App\Models;

use App\Exceptions\ImmutableLedgerException;
use App\Exceptions\InvalidTransaksiException;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

#[Fillable([
    'uuid', 'santri_id', 'jenis', 'arah', 'nominal', 'saldo_sebelum', 'saldo_sesudah',
    'status', 'metode', 'referensi_type', 'referensi_id', 'tagihan_id', 'diproses_oleh',
    'idempotency_key', 'external_reference', 'catatan', 'metadata',
])]
class Transaksi extends Model
{
    public const UPDATED_AT = null;

    public const JENIS_TOPUP_TUNAI = 'topup_tunai';

    public const JENIS_TOPUP_TRANSFER_WALI = 'topup_transfer_wali';

    public const JENIS_PENARIKAN_TUNAI = 'penarikan_tunai';

    public const JENIS_PEMBAYARAN_TAGIHAN = 'pembayaran_tagihan';

    public const JENIS_PENYESUAIAN = 'penyesuaian';

    public const JENIS_PEMBAYARAN_KANTIN = 'pembayaran_kantin';

    public const JENIS_TRANSFER_ANTAR_SANTRI = 'transfer_antar_santri';

    public const JENIS_TRANSFER_KE_TABUNGAN = 'transfer_ke_tabungan';

    public const ARAH_DEBIT = 'debit';

    public const ARAH_KREDIT = 'kredit';

    public const STATUS_PENDING = 'pending';

    public const STATUS_MENUNGGU_VERIFIKASI = 'menunggu_verifikasi';

    public const STATUS_BERHASIL = 'berhasil';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const METODE_TUNAI = 'tunai';

    public const METODE_TRANSFER_BANK = 'transfer_bank';

    public const METODE_MIDTRANS = 'midtrans';

    public const METODE_SISTEM = 'sistem';

    protected function casts(): array
    {
        return [
            'santri_id' => 'integer',
            'tagihan_id' => 'integer',
            'diproses_oleh' => 'integer',
            'referensi_id' => 'integer',
            'nominal' => 'integer',
            'saldo_sebelum' => 'integer',
            'saldo_sesudah' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Transaksi $transaksi) {
            $transaksi->uuid ??= (string) Str::uuid();

            if ($transaksi->jenis === self::JENIS_PENARIKAN_TUNAI) {
                $referensi = $transaksi->referensi;

                if (! $referensi instanceof PenarikanRequest || $referensi->status !== PenarikanRequest::STATUS_DISETUJUI) {
                    throw new InvalidTransaksiException(
                        'Transaksi penarikan_tunai hanya boleh dibuat dari PenarikanRequest yang berstatus disetujui.'
                    );
                }
            }
        });

        static::updating(function () {
            throw new ImmutableLedgerException('Transaksi ledger tidak boleh diubah.');
        });

        static::deleting(function () {
            throw new ImmutableLedgerException('Transaksi ledger tidak boleh dihapus.');
        });
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function referensi(): MorphTo
    {
        return $this->morphTo();
    }

    public function kwitansi(): HasOne
    {
        return $this->hasOne(Kwitansi::class);
    }

    public function mutasiKas(): MorphOne
    {
        return $this->morphOne(MutasiKas::class, 'referensi');
    }

    /**
     * Permintaan penarikan yang menghasilkan transaksi ini.
     *
     * Mutasi kas penarikan melekat pada permintaan agar bukti persetujuan
     * dan verifikasi sidik jari tetap berada dalam satu jejak audit.
     */
    public function penarikanRequest(): HasOne
    {
        return $this->hasOne(PenarikanRequest::class);
    }
}
