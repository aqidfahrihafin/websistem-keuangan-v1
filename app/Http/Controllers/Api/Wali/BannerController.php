<?php

namespace App\Http\Controllers\Api\Wali;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\JsonResponse;

/**
 * Public (no auth) active-banner list for the mobile app's Home tab
 * carousel - same "public, no session needed" shape as AppInfoController,
 * since this is site-wide promotional content, not wali-specific data.
 */
class BannerController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $banners = Banner::where('aktif', true)
            ->orderBy('urutan')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Banner $banner) => [
                'id' => $banner->id,
                'judul' => $banner->judul,
                'gambar_url' => $banner->gambarUrl(),
                'link_url' => $banner->link_url,
            ]);

        return response()->json(['data' => $banners]);
    }
}
