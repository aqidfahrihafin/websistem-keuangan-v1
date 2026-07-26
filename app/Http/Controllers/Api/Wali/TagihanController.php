<?php

namespace App\Http\Controllers\Api\Wali;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\MidtransNotConfiguredException;
use App\Exceptions\SaldoDiBawahMinimumException;
use App\Http\Resources\TagihanResource;
use App\Http\Resources\TopupWaliResource;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Services\PinService;
use App\Services\SaldoFloorService;
use App\Services\TagihanService;
use App\Services\TopupWaliService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class TagihanController extends WaliApiController
{
    public function index(Santri $santri)
    {
        $this->authorizedSantri($santri);

        $tagihans = $santri->tagihans()->with('jenisTagihan')->latest()->get();

        return TagihanResource::collection($tagihans);
    }

    public function bayar(Request $request, Santri $santri, Tagihan $tagihan, TagihanService $service, PinService $pinService, SaldoFloorService $saldoFloor): JsonResponse
    {
        $this->authorizedSantri($santri);

        abort_unless($tagihan->santri_id === $santri->id, 404);

        // nominal is optional - omitted (or null) pays the full sisa, same
        // as before this existed. A smaller amount is only accepted when
        // the tagihan's jenis has bisa_dicicil enabled; bayarDariSaldo()
        // itself is the source of truth for that guard. Upper bound is a
        // fat-finger/abuse safety net, not a real business limit -
        // admin-configurable, see SaldoFloorService.
        $data = $request->validate([
            'nominal' => ['nullable', 'integer', 'min:1', 'max:'.$saldoFloor->maksimalNominal()],
            'pin' => ['required', 'digits:6'],
        ]);

        $this->requirePin($data['pin'], $pinService);

        try {
            $pembayaran = $service->bayarDariSaldo($tagihan, Auth::user(), $data['nominal'] ?? null);
        } catch (SaldoDiBawahMinimumException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'saldo_di_bawah_minimum'], 422);
        } catch (InsufficientBalanceException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'nominal_tidak_valid'], 422);
        }

        if ($pembayaran === null) {
            return response()->json(['message' => 'Tagihan ini sudah lunas.'], 422);
        }

        return response()->json([
            'message' => 'Tagihan berhasil dibayar dari saldo.',
            'tagihan' => new TagihanResource($tagihan->fresh()),
            'kwitansi_id' => $pembayaran->kwitansi?->id,
        ]);
    }

    /**
     * Mobile counterpart to the web wali portal's "Bayar Langsung via
     * Midtrans" button - creates a Core API (VA/QRIS) transaction for
     * exactly this tagihan's remaining amount, same as storeCore() does for
     * a generic top up. No Snap variant here: the mobile app has no
     * WebView/browser-redirect capability, only the native VA/QRIS card UI
     * TopupController::storeCore() already powers for plain top-up.
     */
    public function bayarViaMidtrans(Request $request, Santri $santri, Tagihan $tagihan, TopupWaliService $service): JsonResponse
    {
        $this->authorizedSantri($santri);

        abort_unless($tagihan->santri_id === $santri->id, 404);

        $data = $request->validate([
            'metode' => ['required', 'string', Rule::in([
                TopupWaliService::METODE_BNI_VA,
                TopupWaliService::METODE_BCA_VA,
                TopupWaliService::METODE_BRI_VA,
                TopupWaliService::METODE_QRIS,
            ])],
        ]);

        try {
            $topup = $service->createCoreApiTransactionForTagihan(Auth::user(), $tagihan, $data['metode']);
        } catch (MidtransNotConfiguredException|InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new TopupWaliResource($topup))->response()->setStatusCode(201);
    }
}
