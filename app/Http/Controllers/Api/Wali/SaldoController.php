<?php

namespace App\Http\Controllers\Api\Wali;

use App\Models\Santri;
use Illuminate\Http\JsonResponse;

class SaldoController extends WaliApiController
{
    public function show(Santri $santri): JsonResponse
    {
        $this->authorizedSantri($santri);

        return response()->json([
            'santri_id' => $santri->id,
            'saldo' => $santri->saldo?->saldo ?? 0,
        ]);
    }
}
