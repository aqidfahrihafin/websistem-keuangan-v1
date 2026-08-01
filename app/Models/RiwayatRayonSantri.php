<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['santri_id', 'rayon_id', 'tanggal_mulai', 'tanggal_selesai', 'alasan_perpindahan', 'dicatat_oleh'])]
class RiwayatRayonSantri extends Model
{
    protected function casts(): array
    {
        return ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date'];
    }
}
