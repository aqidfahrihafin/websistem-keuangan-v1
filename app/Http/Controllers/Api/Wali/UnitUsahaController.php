<?php

namespace App\Http\Controllers\Api\Wali;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\LimitKantinHarianException;
use App\Exceptions\SaldoDiBawahMinimumException;
use App\Models\Santri;
use App\Models\UnitUsaha;
use App\Services\KantinPembayaranService;
use App\Services\PinService;
use App\Services\SaldoFloorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class UnitUsahaController extends WaliApiController
{
    /**
     * Looked up by `kode` (not route-model-bound by id) since that's the
     * plain-text value a QR code decodes to - lets the app show a friendly
     * kantin name before asking the wali for a nominal.
     */
    public function show(string $kode): JsonResponse
    {
        $unitUsaha = UnitUsaha::where('kode', $kode)->first();

        abort_if($unitUsaha === null, 404, 'Kantin tidak ditemukan.');
        abort_if($unitUsaha->status !== UnitUsaha::STATUS_AKTIF, 422, 'Kantin ini sedang tidak aktif.');

        return response()->json([
            'kode' => $unitUsaha->kode,
            'nama' => $unitUsaha->nama,
        ]);
    }

    public function bayar(Request $request, Santri $santri, KantinPembayaranService $service, PinService $pinService, SaldoFloorService $saldoFloor): JsonResponse
    {
        $this->authorizedSantri($santri);

        $data = $request->validate([
            'kode' => ['required', 'string'],
            // Upper bound is a fat-finger/abuse safety net, not a real
            // business limit - admin-configurable, see SaldoFloorService.
            'nominal' => ['required', 'integer', 'min:1', 'max:'.$saldoFloor->maksimalNominal()],
            'pin' => ['required', 'digits:6'],
            'request_id' => ['nullable', 'string', 'max:100'],
        ]);

        $this->requirePin($data['pin'], $pinService);

        $unitUsaha = UnitUsaha::where('kode', $data['kode'])->first();

        abort_if($unitUsaha === null, 404, 'Kantin tidak ditemukan.');

        try {
            $transaksi = $service->bayar(
                $santri,
                $unitUsaha,
                $data['nominal'],
                Auth::user(),
                requestId: $data['request_id'] ?? null,
            );
        } catch (InsufficientBalanceException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'saldo_tidak_cukup'], 422);
        } catch (SaldoDiBawahMinimumException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'saldo_di_bawah_minimum'], 422);
        } catch (LimitKantinHarianException $e) {
            return response()->json(['message' => $e->getMessage(), 'code' => 'limit_kantin_harian'], 422);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => "Pembayaran ke {$unitUsaha->nama} berhasil.",
            'unit_usaha' => ['kode' => $unitUsaha->kode, 'nama' => $unitUsaha->nama],
            'id' => $transaksi->id,
            'santri' => ['nama' => $santri->nama, 'nis' => $santri->nis],
            'nominal' => $transaksi->nominal,
            'saldo_sesudah' => $transaksi->saldo_sesudah,
            'dibayar_at' => $transaksi->created_at->toIso8601String(),
            // Issued inside KantinPembayaranService::bayar() (KwitansiService)
            // - lets the mobile success screen offer "Unduh Kwitansi" right
            // away instead of only from Riwayat Transaksi later.
            'kwitansi_id' => $transaksi->kwitansi?->id,
        ]);
    }
}
