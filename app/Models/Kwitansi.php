<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['nomor_kwitansi', 'jenis', 'santri_id', 'nominal', 'tagihan_pembayaran_id', 'transaksi_id', 'topup_wali_id', 'dicetak_oleh', 'dicetak_at'])]
class Kwitansi extends Model
{
    use HasFactory;

    public const JENIS_TAGIHAN = 'tagihan';

    public const JENIS_KANTIN = 'kantin';

    public const JENIS_TOPUP = 'topup';

    protected function casts(): array
    {
        return [
            'dicetak_at' => 'datetime',
        ];
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function tagihanPembayaran(): BelongsTo
    {
        return $this->belongsTo(TagihanPembayaran::class);
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function topupWali(): BelongsTo
    {
        return $this->belongsTo(TopupWali::class);
    }

    public function dicetakOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicetak_oleh');
    }

    public function catatDicetak(User $user): void
    {
        $this->update(['dicetak_oleh' => $user->id, 'dicetak_at' => now()]);
    }
}
