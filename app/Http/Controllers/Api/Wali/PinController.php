<?php

namespace App\Http\Controllers\Api\Wali;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PinService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PinController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json(['has_pin' => Auth::user()->hasPin()]);
    }

    /**
     * Checks the account password on its own, with no side effect - lets
     * the mobile PIN setup screen verify it for real before revealing the
     * PIN form, instead of collecting password + PIN + confirmation all at
     * once and only finding out the password was wrong at the very end.
     */
    public function confirmPassword(Request $request): JsonResponse
    {
        $data = $request->validate(['password' => ['required', 'string']]);

        $this->assertPassword($request->user(), $data['password'], 'password');

        return response()->json(['message' => 'Kata sandi benar.']);
    }

    /**
     * Sets (or replaces) the transaction PIN - requires the account
     * password to prove it's really the account owner doing this, same
     * guard AuthController::password() uses before changing a credential.
     */
    public function store(Request $request, PinService $pinService): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'pin' => ['required', 'digits:6', 'confirmed'],
        ]);

        $user = $request->user();
        $this->assertPassword($user, $data['current_password'], 'current_password');

        $pinService->set($user, $data['pin']);

        return response()->json(['message' => 'PIN transaksi berhasil disimpan.']);
    }

    private function assertPassword(User $user, string $password, string $field): void
    {
        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                $field => ['Kata sandi salah.'],
            ]);
        }
    }
}
