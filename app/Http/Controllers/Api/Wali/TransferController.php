<?php

namespace App\Http\Controllers\Api\Wali;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\SaldoDiBawahMinimumException;
use App\Models\Santri;
use App\Services\PinService;
use App\Services\SaldoFloorService;
use App\Services\TransferSaldoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class TransferController extends WaliApiController
{
    public function transfer(Request $request, Santri $santri, TransferSaldoService $service, PinService $pinService, SaldoFloorService $saldoFloor): JsonResponse
    {
        $this->authorizedSantri($santri);

        $data = $request->validate([
            'ke_santri_id' => ['required', 'integer', 'exists:santris,id'],
            // Upper bound is a fat-finger/abuse safety net, not a real
            // business limit - admin-configurable, see SaldoFloorService.
            'nominal' => ['required', 'integer', 'min:1', 'max:'.$saldoFloor->maksimalNominal()],
            'pin' => ['required', 'digits:6'],
            'request_id' => ['nullable', 'string', 'max:100'],
        ]);

        $this->requirePin($data['pin'], $pinService);

        $ke = Santri::findOrFail($data['ke_santri_id']);

        try {
            $result = $service->transfer(
                $santri,
                $ke,
                $data['nominal'],
                Auth::user(),
                $data['request_id'] ?? null,
            );
        } catch (InsufficientBalanceException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'saldo_tidak_cukup'], 422);
        } catch (SaldoDiBawahMinimumException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'saldo_di_bawah_minimum'], 422);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $debit = $result['debit'];
        $credit = $result['credit'];

        return response()->json([
            'message' => "Transfer ke {$ke->nama} berhasil.",
            'id' => $debit->id,
            'dari' => ['id' => $santri->id, 'nama' => $santri->nama, 'saldo_sesudah' => $debit->saldo_sesudah],
            'ke' => ['id' => $ke->id, 'nama' => $ke->nama, 'saldo_sesudah' => $credit->saldo_sesudah],
            'nominal' => $debit->nominal,
            'dibuat_at' => $debit->created_at->toIso8601String(),
        ]);
    }
}
