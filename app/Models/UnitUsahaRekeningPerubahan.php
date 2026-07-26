<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A kantin owner's request to change their payout bank account - modeled on
 * UnitUsahaPenarikan's shape, but only two outcomes (disetujui/ditolak, no
 * "selesai") since approval itself is the terminal action: it writes the
 * proposed values straight into UnitUsaha's active bank_* fields.
 */
#[Fillable([
    'unit_usaha_id', 'bank_nama_baru', 'bank_no_rekening_baru', 'bank_atas_nama_baru',
    'status', 'diajukan_oleh', 'diajukan_at', 'diproses_oleh', 'diproses_at', 'catatan_petugas',
])]
class UnitUsahaRekeningPerubahan extends Model
{
    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DISETUJUI = 'disetujui';

    public const STATUS_DITOLAK = 'ditolak';

    protected function casts(): array
    {
        return [
            'diajukan_at' => 'datetime',
            'diproses_at' => 'datetime',
        ];
    }

    public function unitUsaha(): BelongsTo
    {
        return $this->belongsTo(UnitUsaha::class);
    }

    public function diajukanOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diajukan_oleh');
    }

    public function diprosesOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }
}
