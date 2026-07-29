<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SantriResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nis' => $this->nis,
            'nama' => $this->nama,
            'jenis_kelamin' => $this->jenis_kelamin,
            'tempat_lahir' => $this->tempat_lahir,
            'tanggal_lahir' => $this->tanggal_lahir?->toDateString(),
            'alamat' => $this->alamat,
            'status' => $this->status,
            'lembaga' => $this->whenLoaded('lembaga', fn () => $this->lembaga?->nama),
            'kamar' => $this->whenLoaded('kamar', fn () => $this->kamar ? [
                'id' => $this->kamar->id,
                'kode' => $this->kamar->kode,
                'nama' => $this->kamar->nama,
            ] : null),
            'foto_url' => $this->foto_path ? asset('storage/'.$this->foto_path) : null,
            // MySQL/PDO can expose BIGINT values as numeric strings on some
            // hosting configurations. Keep the public API contract stable:
            // Flutter expects a JSON number here, never "100000".
            'saldo' => $this->whenLoaded('saldo', fn () => (int) ($this->saldo?->saldo ?? 0), 0),
            // Always present (not $this->when()'d away) - a Santri fetched
            // outside a wali's own anakAsuh pivot (e.g. SaudaraController's
            // plain same-KK query) has no pivot at all, and an omitted key
            // decodes to Dart null, which crashes a non-nullable `as
            // String` cast client-side. null here is a legitimate "this
            // santri has no hubungan to the viewer" answer, not a bug.
            'hubungan' => isset($this->pivot) ? $this->pivot->hubungan : null,
        ];
    }
}
