<?php

namespace App\Http\Controllers\Api\Wali;

use App\Http\Resources\TransaksiResource;
use App\Models\Santri;
use App\Models\Transaksi;

class TransaksiController extends WaliApiController
{
    public function index(Santri $santri)
    {
        $this->authorizedSantri($santri);

        $transaksis = $santri->transaksis()->with(['tagihan.jenisTagihan', 'referensi', 'kwitansi'])->latest()->paginate(20);

        return TransaksiResource::collection($transaksis);
    }

    public function show(Santri $santri, Transaksi $transaksi): TransaksiResource
    {
        $this->authorizedSantri($santri);
        abort_unless($transaksi->santri_id === $santri->id, 404);

        return new TransaksiResource(
            $transaksi->load(['tagihan.jenisTagihan', 'referensi', 'kwitansi'])
        );
    }
}
