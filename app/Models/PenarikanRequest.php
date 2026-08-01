<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'santri_id', 'nominal_diminta', 'status', 'diminta_at', 'dalam_jam_kebijakan',
    'melebihi_limit_harian', 'wajib_surat_keterangan', 'surat_keterangan_path',
    'surat_keterangan_status', 'verifikasi_fingerprint_ref', 'device_id', 'sesi_kas_id',
    'diproses_oleh', 'diproses_at', 'catatan_petugas', 'transaksi_id',
])]
class PenarikanRequest extends Model
{
    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    public const SURAT_MENUNGGU_REVIEW = 'menunggu_review';

    public const SURAT_DISETUJUI = 'disetujui';

    public const SURAT_DITOLAK = 'ditolak';

    protected function casts(): array
    {
        return [
            'diminta_at' => 'datetime',
            'diproses_at' => 'datetime',
            'dalam_jam_kebijakan' => 'boolean',
            'melebihi_limit_harian' => 'boolean',
            'wajib_surat_keterangan' => 'boolean',
            // Encrypted at rest - write-only (never looked up via WHERE
            // anywhere in the app), so unlike KartuSantri's uid_kartu/
            // fingerprint_template_ref this needs no companion hash column.
            'verifikasi_fingerprint_ref' => 'encrypted',
        ];
    }

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    public function sesiKas(): BelongsTo
    {
        return $this->belongsTo(SesiKas::class);
    }

    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function transaksiTerkait(): MorphMany
    {
        return $this->morphMany(Transaksi::class, 'referensi');
    }

    public function siapDieksekusi(): bool
    {
        if ($this->status !== self::STATUS_DISETUJUI) {
            return false;
        }

        if ($this->wajib_surat_keterangan && $this->surat_keterangan_status !== self::SURAT_DISETUJUI) {
            return false;
        }

        return true;
    }
}
