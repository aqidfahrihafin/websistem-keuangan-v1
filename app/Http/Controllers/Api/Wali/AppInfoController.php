<?php

namespace App\Http\Controllers\Api\Wali;

use App\Http\Controllers\Controller;
use App\Services\AppSettingsService;
use Illuminate\Http\JsonResponse;

/**
 * Public (no auth) branding info for the mobile app's splash/login screens,
 * which render before any session exists - mirrors AppSettingsService's
 * text branding already used everywhere on the web side.
 */
class AppInfoController extends Controller
{
    public function __invoke(AppSettingsService $service): JsonResponse
    {
        return response()->json([
            'nama_aplikasi' => $service->namaAplikasi(),
            'nama_pondok' => $service->namaPondok(),
            'logo_url' => $service->logoUrl(),
        ]);
    }
}
