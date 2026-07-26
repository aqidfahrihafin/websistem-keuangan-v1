<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagihanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'jenis_tagihan' => [
                'kode' => $this->jenisTagihan->kode,
                'nama' => $this->jenisTagihan->nama,
                'bisa_dicicil' => $this->jenisTagihan->bisa_dicicil,
            ],
            'periode_label' => $this->periode_label,
            'nominal' => $this->nominal,
            'nominal_sebelum_diskon' => $this->nominal_sebelum_diskon,
            'diskon_persen' => $this->diskon_persen,
            'nominal_terbayar' => $this->nominal_terbayar,
            'sisa' => $this->sisa(),
            'status' => $this->status,
            'jatuh_tempo' => $this->jatuh_tempo?->toDateString(),
        ];
    }
}
