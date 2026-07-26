<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Withdrawal request for a UnitUsaha's collected saldo_unit - modeled on
 * PenarikanRequest's menunggu/disetujui/selesai state machine, deliberately
 * without the surat-keterangan/kebijakan-jam/fingerprint machinery there
 * (santri-pocket-money-specific policy, not relevant to a business-unit
 * payout approved entirely within the admin panel).
 */
#[Fillable([
    'unit_usaha_id', 'nominal_diminta', 'status', 'diminta_oleh', 'diminta_at',
    'diproses_oleh', 'diproses_at', 'catatan_petugas', 'unit_usaha_transaksi_id',
    'referensi_transfer',
    'metode_pencairan', 'bank_nama_tujuan', 'bank_no_rekening_tujuan',
    'bank_atas_nama_tujuan', 'kode_serah_terima', 'diserahkan_at',
    'dikonfirmasi_oleh', 'dikonfirmasi_at',
])]
class UnitUsahaPenarikan extends Model
{
    public const METODE_TRANSFER_BANK = 'transfer_bank';

    public const METODE_TUNAI = 'tunai';

    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_SELESAI = 'selesai';

    protected function casts(): array
    {
        return [
            'diminta_at' => 'datetime',
            'diproses_at' => 'datetime',
            'diserahkan_at' => 'datetime',
            'dikonfirmasi_at' => 'datetime',
            'kode_serah_terima' => 'encrypted',
        ];
    }

    public function unitUsaha(): BelongsTo
    {
        return $this->belongsTo(UnitUsaha::class);
    }

    public function dimintaOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diminta_oleh');
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function dikonfirmasiOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikonfirmasi_oleh');
    }

    public function metodeLabel(): string
    {
        return $this->metode_pencairan === self::METODE_TUNAI ? 'Tunai' : 'Transfer Bank';
    }

    public function unitUsahaTransaksi(): BelongsTo
    {
        return $this->belongsTo(UnitUsahaTransaksi::class);
    }
}
