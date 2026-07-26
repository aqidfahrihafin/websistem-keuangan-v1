<?php

namespace App\Http\Controllers\Api\Wali;

use App\Http\Resources\SantriResource;
use App\Models\Santri;

class SaudaraController extends WaliApiController
{
    /**
     * Santri sharing the same Kartu Keluarga as {santri} - the candidate
     * recipient list for a transfer, restricted to active santri only.
     * Deliberately not restricted further to the caller's own anakAsuh:
     * a keluarga can have more than one wali account (see
     * WaliAccountService), and "1 KK" is the agreed transfer boundary.
     */
    public function index(Santri $santri)
    {
        $this->authorizedSantri($santri);

        $saudara = Santri::where('keluarga_id', $santri->keluarga_id)
            ->where('id', '!=', $santri->id)
            ->where('status', Santri::STATUS_AKTIF)
            ->with(['lembaga', 'kamar'])
            ->orderBy('nama')
            ->get();

        return SantriResource::collection($saudara);
    }
}
