<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['santri_id', 'saldo'])]
class SaldoSantri extends Model
{
    protected $primaryKey = 'santri_id';

    public $incrementing = false;

    protected $keyType = 'int';

    const CREATED_AT = null;

    public function santri(): BelongsTo
    {
        return $this->belongsTo(Santri::class);
    }
}
