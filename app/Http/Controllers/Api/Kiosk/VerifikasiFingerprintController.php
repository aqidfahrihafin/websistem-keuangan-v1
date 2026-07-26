<?php

namespace App\Http\Controllers\Api\Kiosk;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\PenarikanRequest;
use App\Services\FingerprintVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifikasiFingerprintController extends Controller
{
    /**
     * The kiosk device performs the actual biometric match locally and calls
     * this endpoint to assert the result. This only records the assertion on
     * the request - it never moves money. A pengurus still has to fulfil the
     * request from the admin console, which re-checks this same reference.
     */
    public function __invoke(Request $request, FingerprintVerifier $verifier): JsonResponse
    {
        $data = $request->validate([
            'penarikan_request_id' => ['required', 'integer', 'exists:penarikan_requests,id'],
            'fingerprint_ref' => ['required', 'string'],
        ]);

        $penarikanRequest = PenarikanRequest::with('santri')->findOrFail($data['penarikan_request_id']);

        if ($penarikanRequest->status !== PenarikanRequest::STATUS_DISETUJUI) {
            return response()->json(['message' => 'Request penarikan belum disetujui petugas.'], 422);
        }

        if (! $verifier->verify($data['fingerprint_ref'], $penarikanRequest->santri)) {
            return response()->json(['message' => 'Verifikasi sidik jari gagal.'], 422);
        }

        /** @var Device $device */
        $device = $request->user();

        $penarikanRequest->update([
            'verifikasi_fingerprint_ref' => $data['fingerprint_ref'],
            'device_id' => $device->id,
        ]);

        return response()->json(['message' => 'Verifikasi berhasil, silakan lanjutkan ke petugas untuk pencairan.']);
    }
}
