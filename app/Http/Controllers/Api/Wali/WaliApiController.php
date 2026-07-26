<?php

namespace App\Http\Controllers\Api\Wali;

use App\Exceptions\PinLockedException;
use App\Http\Controllers\Controller;
use App\Models\Santri;
use App\Services\PinService;
use Illuminate\Support\Facades\Auth;

abstract class WaliApiController extends Controller
{
    /**
     * Every {santri} route parameter must be verified to actually belong to
     * the authenticated wali before any data is returned or mutated - route
     * model binding alone does not check ownership.
     */
    protected function authorizedSantri(Santri $santri): Santri
    {
        $tautan = Auth::user()->anakAsuh()->where('santris.id', $santri->id)->exists();

        abort_unless($tautan, 403, 'Santri ini tidak tertaut dengan akun Anda.');

        return $santri;
    }

    /**
     * Shared gate for every sensitive money action (kantin payment,
     * tagihan-from-saldo, transfer antar santri) - aborts the request
     * outright (422 wrong PIN, 423 locked out) rather than making each
     * caller repeat its own try/catch around PinService::verify().
     */
    protected function requirePin(string $pin, PinService $pinService): void
    {
        try {
            $valid = $pinService->verify(Auth::user(), $pin);
        } catch (PinLockedException $e) {
            abort(423, $e->getMessage());
        }

        abort_unless($valid, 422, 'PIN salah.');
    }
}
