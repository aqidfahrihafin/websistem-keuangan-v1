<?php

namespace App\Http\Controllers\Api\Wali;

use App\Exceptions\MidtransNotConfiguredException;
use App\Http\Resources\TopupWaliResource;
use App\Models\Santri;
use App\Services\PinService;
use App\Services\SaldoFloorService;
use App\Services\TabunganService;
use App\Services\TopupWaliService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class TabunganController extends WaliApiController
{
    public function show(Santri $santri, TabunganService $service): JsonResponse
    {
        $this->authorizedSantri($santri);
        $rekening = $santri->rekeningTabungan;

        return response()->json([
            'santri_id' => $santri->id,
            'saldo_santri' => (int) ($santri->saldo?->saldo ?? 0),
            'saldo_tabungan' => (int) ($rekening?->saldo ?? 0),
            'saldo_bisa_dipindahkan' => $service->saldoBisaDitabung($santri),
            'status' => $rekening?->status ?? 'belum_dibuka',
            'transaksi' => $rekening?->transaksi()->latest()->limit(20)->get()->map(fn ($item) => [
                'id' => $item->id,
                'jenis' => $item->jenis,
                'kanal' => $item->kanal,
                'arah' => $item->arah,
                'nominal' => (int) $item->nominal,
                'saldo_sesudah' => (int) $item->saldo_sesudah,
                'dibuat_at' => $item->created_at->toIso8601String(),
            ])->values() ?? [],
        ]);
    }

    public function dariSaldo(
        Request $request,
        Santri $santri,
        TabunganService $service,
        PinService $pinService,
        SaldoFloorService $batasSaldo,
    ): JsonResponse {
        $this->authorizedSantri($santri);
        $data = $request->validate([
            'nominal' => ['required', 'integer', 'min:1000', 'max:'.$batasSaldo->maksimalNominal()],
            'pin' => ['required', 'digits:6'],
            'request_id' => ['nullable', 'string', 'max:100'],
        ]);
        $this->requirePin($data['pin'], $pinService);

        try {
            $transaksi = $service->pindahDariSaldo(
                $santri,
                $data['nominal'],
                Auth::user(),
                \App\Models\TransaksiTabungan::KANAL_WALI,
                $data['request_id'] ?? 'wali-'.Str::uuid(),
            );
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Saldo berhasil dipindahkan ke tabungan.',
            'transaksi_id' => $transaksi->id,
            'nominal' => (int) $transaksi->nominal,
            'saldo_santri' => (int) ($santri->fresh()->saldo?->saldo ?? 0),
            'saldo_tabungan' => (int) $transaksi->saldo_sesudah,
        ], 201);
    }

    public function viaMidtrans(
        Request $request,
        Santri $santri,
        TopupWaliService $service,
        SaldoFloorService $batasSaldo,
    ): JsonResponse {
        $this->authorizedSantri($santri);
        $data = $request->validate([
            'nominal' => ['required', 'integer', 'min:10000', 'max:'.$batasSaldo->maksimalNominal()],
            'metode' => ['required', Rule::in([
                TopupWaliService::METODE_BNI_VA,
                TopupWaliService::METODE_BCA_VA,
                TopupWaliService::METODE_BRI_VA,
                TopupWaliService::METODE_QRIS,
            ])],
        ]);

        try {
            $topup = $service->createCoreApiTransactionForTabungan(
                Auth::user(),
                $santri,
                $data['nominal'],
                $data['metode'],
            );
        } catch (MidtransNotConfiguredException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new TopupWaliResource($topup))->response()->setStatusCode(201);
    }
}
