<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['tagihan_id', 'nominal', 'sumber', 'transaksi_id', 'topup_wali_id', 'dicatat_oleh', 'dibayar_at'])]
class TagihanPembayaran extends Model
{
    public const SUMBER_TUNAI_LANGSUNG = 'tunai_langsung';

    public const SUMBER_SALDO = 'saldo';

    /** @deprecated Legacy value from the removed auto-sweep behavior - kept for historical rows, never written by new code. */
    public const SUMBER_TRANSFER_WALI_OTOMATIS = 'transfer_wali_otomatis';

    public const SUMBER_TRANSFER_WALI_TAGIHAN = 'transfer_wali_tagihan';

    public const SUMBER_LABEL = [
        self::SUMBER_TUNAI_LANGSUNG => 'Tunai Langsung',
        self::SUMBER_SALDO => 'Saldo',
        self::SUMBER_TRANSFER_WALI_OTOMATIS => 'Transfer Wali (Otomatis)',
        self::SUMBER_TRANSFER_WALI_TAGIHAN => 'Transfer Wali (Bayar Langsung)',
    ];

    protected function casts(): array
    {
        return [
            'dibayar_at' => 'datetime',
        ];
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function topupWali(): BelongsTo
    {
        return $this->belongsTo(TopupWali::class);
    }

    public function dicatatOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh');
    }

    public function kwitansi(): HasOne
    {
        return $this->hasOne(Kwitansi::class);
    }
}
