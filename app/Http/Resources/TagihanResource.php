<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TagihanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $jenisTagihan = $this->whenLoaded('jenisTagihan') ? $this->jenisTagihan : null;

        return [
            'id' => $this->id,
            'jenis_tagihan' => $jenisTagihan ? [
                'kode' => $jenisTagihan->kode,
                'nama' => $jenisTagihan->nama,
                'bisa_dicicil' => (bool) $jenisTagihan->bisa_dicicil,
            ] : null,
            'periode_label' => $this->periode_label,
            'nominal' => (int) $this->nominal,
            'nominal_sebelum_diskon' => $this->nominal_sebelum_diskon !== null
                ? (int) $this->nominal_sebelum_diskon
                : null,
            'diskon_persen' => $this->diskon_persen !== null ? (int) $this->diskon_persen : null,
            'nominal_terbayar' => (int) $this->nominal_terbayar,
            'sisa' => (int) $this->sisa(),
            'status' => $this->status,
            'jatuh_tempo' => $this->jatuh_tempo?->toDateString(),
        ];
    }
}
