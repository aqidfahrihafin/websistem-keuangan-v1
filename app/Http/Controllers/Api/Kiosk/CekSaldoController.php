<?php

namespace App\Http\Controllers\Api\Kiosk;

use App\Http\Controllers\Controller;
use App\Models\KartuSantri;
use App\Services\FingerprintVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CekSaldoController extends Controller
{
    public function __invoke(Request $request, FingerprintVerifier $verifier): JsonResponse
    {
        $data = $request->validate([
            'nomor_kartu' => ['required', 'string'],
            'fingerprint_ref' => ['required', 'string'],
        ]);

        $kartu = KartuSantri::with('santri.saldo')
            ->where('nomor_kartu', $data['nomor_kartu'])
            ->where('status', KartuSantri::STATUS_AKTIF)
            ->first();

        if (! $kartu || ! $verifier->verify($data['fingerprint_ref'], $kartu->santri)) {
            return response()->json(['message' => 'Kartu atau verifikasi sidik jari tidak valid.'], 422);
        }

        $santri = $kartu->santri;

        return response()->json([
            'nama' => $santri->nama,
            'nis' => $santri->nis,
            'saldo' => $santri->saldo?->saldo ?? 0,
            'tagihan_belum_lunas' => $santri->tagihans()->whereIn('status', ['belum_lunas', 'sebagian'])->count(),
        ]);
    }
}
