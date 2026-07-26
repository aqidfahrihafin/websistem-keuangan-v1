<?php

namespace App\Http\Controllers\Api\Wali;

use App\Http\Resources\TransaksiResource;
use App\Models\Santri;

class TransaksiController extends WaliApiController
{
    public function index(Santri $santri)
    {
        $this->authorizedSantri($santri);

        $transaksis = $santri->transaksis()->with(['tagihan.jenisTagihan', 'referensi', 'kwitansi'])->latest()->paginate(20);

        return TransaksiResource::collection($transaksis);
    }
}
