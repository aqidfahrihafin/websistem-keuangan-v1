<?php

namespace App\Http\Controllers;

use App\Services\TopupWaliService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MidtransWebhookController extends Controller
{
    public function __invoke(Request $request, TopupWaliService $service): JsonResponse
    {
        $topup = $service->handleWebhook($request->all());

        return response()->json(['status' => $topup->status]);
    }
}
