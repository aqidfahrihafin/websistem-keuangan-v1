<?php

namespace App\Http\Controllers\Api\Wali;

use App\Exceptions\MidtransNotConfiguredException;
use App\Http\Resources\TopupWaliResource;
use App\Models\Santri;
use App\Models\TopupWali;
use App\Services\MidtransFeeService;
use App\Services\SaldoFloorService;
use App\Services\TopupWaliService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TopupController extends WaliApiController
{
    public function store(Request $request, Santri $santri, TopupWaliService $service, SaldoFloorService $saldoFloor): JsonResponse
    {
        $this->authorizedSantri($santri);

        $data = $request->validate([
            // Upper bound is a fat-finger/abuse safety net, not a real
            // business limit - admin-configurable, see SaldoFloorService.
            'nominal' => ['required', 'integer', 'min:10000', 'max:'.$saldoFloor->maksimalNominal()],
        ]);

        try {
            $topup = $service->createSnapTransaction(Auth::user(), $santri, $data['nominal']);
        } catch (MidtransNotConfiguredException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new TopupWaliResource($topup))->response()->setStatusCode(201);
    }

    /**
     * Same as store(), but charges via Midtrans's Core API instead of Snap
     * so a custom in-app payment UI can be built (a VA number or QR image
     * to render directly) instead of redirecting to Midtrans's hosted page.
     */
    public function storeCore(Request $request, Santri $santri, TopupWaliService $service, SaldoFloorService $saldoFloor): JsonResponse
    {
        $this->authorizedSantri($santri);

        $data = $request->validate([
            // Upper bound is a fat-finger/abuse safety net, not a real
            // business limit - admin-configurable, see SaldoFloorService.
            // Checked against this typed nominal, not the eventual Midtrans
            // gross_amount - a wali-borne MidtransFeeService surcharge can
            // legitimately push the real charge a little above this ceiling
            // by design, don't "fix" this into validating gross instead.
            'nominal' => ['required', 'integer', 'min:10000', 'max:'.$saldoFloor->maksimalNominal()],
            'metode' => ['required', 'string', Rule::in([
                TopupWaliService::METODE_BNI_VA,
                TopupWaliService::METODE_BCA_VA,
                TopupWaliService::METODE_BRI_VA,
                TopupWaliService::METODE_QRIS,
            ])],
        ]);

        try {
            $topup = $service->createCoreApiTransaction(Auth::user(), $santri, $data['nominal'], $data['metode']);
        } catch (MidtransNotConfiguredException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return (new TopupWaliResource($topup))->response()->setStatusCode(201);
    }

    /**
     * The floor no longer gates top-up (a top-up always credits 100% to
     * saldo) - it now gates paying a tagihan *from saldo* instead (see
     * SaldoFloorService, TagihanService::bayarDariSaldo()). Route path and
     * JSON key are kept unchanged for backward compatibility with
     * already-shipped mobile app builds that read this exact field.
     */
    public function pengaturan(SaldoFloorService $service, MidtransFeeService $feeService): JsonResponse
    {
        return response()->json([
            'minimal_saldo_setelah_topup' => $service->minimal(),
            'maksimal_nominal_transaksi' => $service->maksimalNominal(),
            // Raw config (not a pre-computed amount) so the app can compute
            // a live estimate as the wali types a custom nominal - a flat
            // number wouldn't work for percentage-based channels.
            // biaya_dibebankan_wali is the plain top-up context (kept
            // unchanged for backward compatibility with already-shipped
            // mobile builds - see docblock above); biaya_dibebankan_wali_tagihan
            // is the same setting for a direct-to-tagihan Midtrans payment,
            // which can now be configured independently (see
            // MidtransFeeService::dibebankanWali()).
            'biaya_dibebankan_wali' => $feeService->dibebankanWali(),
            'biaya_dibebankan_wali_tagihan' => $feeService->dibebankanWali(untukTagihan: true),
            'biaya_channel' => $feeService->semuaChannel(),
        ]);
    }

    public function show(TopupWali $topup): TopupWaliResource
    {
        $this->authorizedSantri($topup->santri);

        return new TopupWaliResource($topup);
    }

    /**
     * Actively pulls the current status from Midtrans instead of waiting on
     * the webhook - useful when polling show() shows a topup stuck on
     * "pending" for longer than expected.
     */
    public function sync(TopupWali $topup, TopupWaliService $service): TopupWaliResource
    {
        $this->authorizedSantri($topup->santri);

        return new TopupWaliResource($service->syncStatusFromMidtrans($topup));
    }
}
