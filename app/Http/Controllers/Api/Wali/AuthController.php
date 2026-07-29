<?php

namespace App\Http\Controllers\Api\Wali;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\KeluargaLinkingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request, KeluargaLinkingService $linking): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = $this->resolveLoginUser(trim($data['login']));

        if (! $user || ! Auth::getProvider()->validateCredentials($user, ['password' => $data['password']])) {
            activity('auth')->withProperties(['login' => $data['login'], 'ip' => $request->ip(), 'guard' => 'api-wali'])->log('Percobaan login API gagal');

            throw ValidationException::withMessages([
                'login' => ['Email/No. KK atau kata sandi salah.'],
            ]);
        }

        if (! $user->hasRole('wali')) {
            throw ValidationException::withMessages([
                'login' => ['Akun ini bukan akun wali santri.'],
            ]);
        }

        $linking->syncForUser($user->fresh());

        $token = $user->createToken($data['device_name'], ['wali'])->plainTextToken;

        activity('auth')->causedBy($user)->withProperties(['ip' => $request->ip(), 'guard' => 'api-wali', 'device_name' => $data['device_name']])->log('Login API berhasil');

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($this->userPayload($request->user()));
    }

    /**
     * Mirrors Profil::simpanProfil() on the web side - name/email/phone are
     * the only wali-editable fields (nis/no_kk are admin-only everywhere,
     * see Profil::simpanProfil() for the same restriction on the web).
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);

        $user->update($data);

        return response()->json($this->userPayload($user->fresh()));
    }

    /**
     * Mirrors Profil::simpanPassword() on the web side - needed here so a
     * wali whose account was auto-created with No. KK as a temporary
     * password (must_change_password=true, see WaliAccountService) has an
     * API-only path to satisfy that requirement without falling back to the
     * web portal.
     */
    public function password(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Kata sandi saat ini salah.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($data['password']),
            'must_change_password' => false,
        ]);

        return response()->json(['message' => 'Kata sandi berhasil diubah.']);
    }

    /**
     * Accepts email or No. KK (same detection + ambiguity guard as the web
     * Auth/LoginForm - a No. KK only resolves while exactly one account
     * uses it, since WaliAccountService allows several wali per keluarga).
     * NIS is deliberately not accepted here - this endpoint is wali-only.
     */
    private function resolveLoginUser(string $login): ?User
    {
        if (str_contains($login, '@')) {
            return User::where('email', $login)->first();
        }

        if (preg_match('/^\d{16}$/', $login)) {
            return User::where('no_kk', $login)->count() === 1
                ? User::where('no_kk', $login)->first()
                : null;
        }

        return null;
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'must_change_password' => $user->must_change_password,
        ];
    }
}
