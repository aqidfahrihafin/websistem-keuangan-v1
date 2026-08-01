<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'title', 'body', 'type', 'data', 'read_at'])]
class WaliNotification extends Model
{
    protected function casts(): array
    {
        return [
            // MySQL can expose BIGINT foreign keys as numeric strings. The
            // notification ownership endpoint compares this value strictly.
            'user_id' => 'integer',
            'data' => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
