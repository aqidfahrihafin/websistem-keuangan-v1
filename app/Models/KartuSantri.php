<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

#[Fillable([
    'santri_id', 'nomor_kartu', 'uid_kartu', 'fingerprint_template_ref', 'status',
    'diaktifkan_oleh', 'diaktifkan_at', 'dinonaktifkan_oleh', 'dinonaktifkan_at', 'alasan_nonaktif',
    'jumlah_cetak', 'dicetak_pertama_at', 'dicetak_terakhir_at', 'dicetak_terakhir_oleh',
])]
class KartuSantri extends Model
{
    use HasFactory, LogsActivity;

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_NONAKTIF = 'nonaktif';

    public const STATUS_HILANG = 'hilang';

    public const STATUS_DIBLOKIR = 'diblokir';

    protected function casts(): array
    {
        return [
            'diaktifkan_at' => 'datetime',
            'dinonaktifkan_at' => 'datetime',
            'dicetak_pertama_at' => 'datetime',
            'dicetak_terakhir_at' => 'datetime',
            // Encrypted at rest (RFID card UID / fingerprint match
            // reference) - see hashReference() and the *_hash columns for
            // why lookups never compare against these two directly.
            'uid_kartu' => 'encrypted',
            'fingerprint_template_ref' => 'encrypted',
        ];
    }

    protected static function booted(): void
    {
        // Keeps the *_hash "blind index" columns in sync with the
        // encrypted values on every save, regardless of which code path
        // writes them - unconditional (not isDirty()-gated) since HMAC is
        // cheap and this guarantees the hash can never silently drift out
        // of sync with the encrypted value.
        static::saving(function (self $kartu) {
            $kartu->uid_kartu_hash = $kartu->uid_kartu !== null ? self::hashReference($kartu->uid_kartu) : null;
            $kartu->fingerprint_template_ref_hash = $kartu->fingerprint_template_ref !== null ? self::hashReference($kartu->fingerprint_template_ref) : null;
        });
    }

    /**
     * Deterministic HMAC-SHA256 "blind index" for uid_kartu/
     * fingerprint_template_ref, keyed with APP_KEY (not a bare sha256(),
     * so it can't be reversed by guessing/dictionary attack against a
     * leaked DB alone). uid_kartu/fingerprint_template_ref are encrypted
     * (non-deterministic ciphertext - see casts()), so a WHERE clause can
     * never match them directly; every lookup (Kios\CekSaldo::scan(),
     * TrustedDeviceFingerprintVerifier::verify(), the admin activation
     * form's duplicate-UID check) compares against this hash instead.
     */
    public static function hashReference(string $value): string
    {
        return hash_hmac('sha256', $value, config('app.key'));
    }

    /**
     * Next "KRT-######" number after the highest one ever used - based on
     * the actual max numeric suffix seen (not just count()), so it stays
     * correct even if a santri's replacement card means the row count no
     * longer lines up with how many numbers have actually been issued.
     * nomor_kartu is never encrypted (it's the number printed on the
     * physical card, not a security credential), so this is a plain scan.
     */
    public static function nomorKartuBerikutnya(): string
    {
        $max = static::query()
            ->where('nomor_kartu', 'like', 'KRT-%')
            ->selectRaw('MAX(CAST(SUBSTRING(nomor_kartu, 5) AS UNSIGNED)) as terbesar')
            ->value('terbesar');

        return 'KRT-'.str_pad((string) (((int) $max) + 1), 6, '0', STR_PAD_LEFT);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // uid_kartu excluded - logging it here would write the
            // decrypted value straight into activity_log.properties,
            // defeating the point of encrypting it (same reasoning as
            // Setting::getActivitylogOptions()). fingerprint_template_ref
            // was already excluded.
            ->logOnly(['nomor_kartu', 'status', 'alasan_nonaktif'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function diaktifkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diaktifkan_oleh');
    }

    public function dinonaktifkanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dinonaktifkan_oleh');
    }

    public function dicetakTerakhirOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicetak_terakhir_oleh');
    }

    /**
     * Null dicetak_pertama_at is the single source of truth for "never
     * printed" - used both for the admin filter and to decide whether the
     * reprint confirmation dialog is needed (a first print never warns).
     */
    public function sudahPernahDicetak(): bool
    {
        return $this->dicetak_pertama_at !== null;
    }

    /**
     * Called once per kartu for every real "Cetak Kartu"/"Cetak Semua"
     * download (never for a Preview) - see KartuCardController. Kept as an
     * explicit activity() call (not just relying on getActivitylogOptions()'s
     * logOnlyDirty()) so the log reads as a purpose-built "Kartu dicetak"
     * entry instead of a generic "updated" diff, giving admins a real
     * riwayat cetak (activity log, filtered per kartu) instead of just a
     * running counter.
     *
     * A double-click (or any near-simultaneous double request) on a plain
     * download link fires two separate requests, and PDF generation isn't
     * instant - both can reach here before either has committed, so a naive
     * "is dicetak_terakhir_at recent?" check on the already-loaded $this
     * wouldn't help (both requests would be reading the same stale value).
     * Re-fetching with lockForUpdate() inside a transaction forces the two
     * requests to serialize on the row lock instead: the second one to
     * acquire the lock sees the first one's already-committed
     * dicetak_terakhir_at and is folded into the same print event rather
     * than counted as a second one.
     */
    public function catatCetak(): void
    {
        DB::transaction(function () {
            $kartu = self::query()->lockForUpdate()->findOrFail($this->id);

            if ($kartu->dicetak_terakhir_at !== null && $kartu->dicetak_terakhir_at->diffInSeconds(now()) < 5) {
                $this->setRawAttributes($kartu->getAttributes());

                return;
            }

            $userId = Auth::id();

            $kartu->update([
                'jumlah_cetak' => $kartu->jumlah_cetak + 1,
                'dicetak_pertama_at' => $kartu->dicetak_pertama_at ?? now(),
                'dicetak_terakhir_at' => now(),
                'dicetak_terakhir_oleh' => $userId,
            ]);

            activity('kartu-cetak')
                ->performedOn($kartu)
                ->causedBy($userId)
                ->withProperties(['nomor_kartu' => $kartu->nomor_kartu, 'ke' => $kartu->jumlah_cetak])
                ->log("Kartu {$kartu->nomor_kartu} dicetak (ke-{$kartu->jumlah_cetak}).");

            // Keep $this in sync - KartuCardController reads jumlah_cetak
            // etc. off the original instance right after this call.
            $this->setRawAttributes($kartu->getAttributes());
        });
    }
}
