<?php

namespace App\Http\Controllers\Api\Wali;

use App\Models\Kwitansi;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;

class KwitansiController extends WaliApiController
{
    /**
     * Returns a short-lived signed URL rather than streaming the PDF
     * directly - the mobile app has no simple way to attach its Bearer
     * token to a plain browser/external-app open (see BannerCarousel's
     * url_launcher usage), so ownership is checked once here, then handed
     * off as a time-limited link the app can just launch.
     */
    public function show(Kwitansi $kwitansi): JsonResponse
    {
        $this->authorizedSantri($kwitansi->santri);

        return response()->json([
            'nomor_kwitansi' => $kwitansi->nomor_kwitansi,
            'pdf_url' => URL::temporarySignedRoute('kwitansi.pdf.signed', now()->addMinutes(15), ['kwitansi' => $kwitansi->id]),
        ]);
    }
}
