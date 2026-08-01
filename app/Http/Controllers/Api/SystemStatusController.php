<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MaintenanceModeService;
use Illuminate\Http\JsonResponse;

class SystemStatusController extends Controller
{
    public function __invoke(MaintenanceModeService $maintenance): JsonResponse
    {
        return response()->json(['data' => $maintenance->publicStatus()]);
    }
}
