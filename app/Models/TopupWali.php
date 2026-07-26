<?php

namespace App\Models;

use App\Services\TopupWaliService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

#[Fillable([
    'uuid', 'user_id', 'santri_id', 'tagihan_id', 'nominal_diminta', 'midtrans_order_id',
    'midtrans_transaction_id', 'snap_token', 'redirect_url', 'status', 'nominal_potongan_tagihan',
    'nominal_ke_saldo', 'biaya_midtrans', 'biaya_ditanggung_wali', 'paid_at', 'raw_notification',
    'payment_type', 'va_bank', 'va_number', 'qr_url', 'expiry_time',
])]
class TopupWali extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_REFUNDED = 'refunded';

    protected static function booted(): void
    {
        static::creating(function (TopupWali $topup) {
            $topup->uuid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'raw_notification' => 'array',
            'expiry_time' => 'datetime',
            'biaya_ditanggung_wali' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function tagihan(): BelongsTo
    {
        return $this->belongsTo(Tagihan::class);
    }

    public function tagihanPembayarans(): HasMany
    {
        return $this->hasMany(TagihanPembayaran::class);
    }

    public function kwitansi(): HasOne
    {
        return $this->hasOne(Kwitansi::class);
    }

    /**
     * The specific channel used (bni_va/bca_va/bri_va/qris), same kode the
     * mobile app's own top-up method picker uses. A Core API top up already
     * has this in payment_type from creation; a Snap top up only gets it
     * once TopupWaliService::handleWebhook() copies it over from the
     * Midtrans notification (payment_type is generic 'bank_transfer' there,
     * so va_bank is what actually carries the specific bank). Null if
     * genuinely unknown (e.g. an old row from before this was tracked).
     */
    public function metodeKode(): ?string
    {
        if (array_key_exists($this->payment_type, TopupWaliService::BANK_VA_METODE) || $this->payment_type === TopupWaliService::METODE_QRIS) {
            return $this->payment_type;
        }

        if ($this->va_bank) {
            return array_search(strtolower($this->va_bank), TopupWaliService::BANK_VA_METODE, true) ?: null;
        }

        return null;
    }

    /**
     * Human-readable label for metodeKode() - "BNI VA"/"QRIS"/etc, or a
     * generic "Midtrans" fallback when the specific channel isn't known,
     * so an admin reading the top up list never has to open the raw
     * Midtrans notification JSON just to see which bank was used.
     */
    public function metodeLabel(): string
    {
        return match ($this->metodeKode()) {
            'bni_va' => 'BNI VA',
            'bca_va' => 'BCA VA',
            'bri_va' => 'BRIVA',
            'qris' => 'QRIS',
            default => 'Midtrans',
        };
    }
}
