<?php

namespace App\Http\Controllers\Api\Wali;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\KeluargaLinkingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

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

        $expirationDays = max(1, (int) config('security.wali_token_expiration_days', 30));
        $token = $user->createToken(
            $data['device_name'],
            ['wali'],
            now()->addDays($expirationDays)
        )->plainTextToken;
        $quickToken = $user->createToken(
            $data['device_name'].' (login cepat)',
            ['wali-quick-login'],
            now()->addDays(max(30, (int) config('security.wali_quick_token_expiration_days', 365)))
        )->plainTextToken;

        activity('auth')->causedBy($user)->withProperties(['ip' => $request->ip(), 'guard' => 'api-wali', 'device_name' => $data['device_name']])->log('Login API berhasil');

        return response()->json([
            'token' => $token,
            'quick_token' => $quickToken,
            'user' => $this->userPayload($user),
        ]);
    }

    public function quickLogin(Request $request): JsonResponse
    {
        $data = $request->validate([
            'quick_token' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $quickToken = PersonalAccessToken::findToken($data['quick_token']);
        $user = $quickToken?->tokenable;

        if (! $quickToken
            || ! $quickToken->can('wali-quick-login')
            || ($quickToken->expires_at && $quickToken->expires_at->isPast())
            || ! $user instanceof User
            || ! $user->hasRole('wali')) {
            return response()->json([
                'message' => 'Sesi login cepat sudah tidak berlaku.',
            ], 401);
        }

        $expirationDays = max(1, (int) config('security.wali_token_expiration_days', 30));
        $quickExpirationDays = max(30, (int) config('security.wali_quick_token_expiration_days', 365));

        // Rotasi kedua token mencegah token login cepat yang pernah dipakai
        // terus berlaku tanpa batas. Hanya perangkat yang menerima token baru
        // yang dapat melakukan pemulihan berikutnya.
        $quickToken->delete();

        return response()->json([
            'token' => $user->createToken(
                $data['device_name'],
                ['wali'],
                now()->addDays($expirationDays)
            )->plainTextToken,
            'quick_token' => $user->createToken(
                $data['device_name'].' (login cepat)',
                ['wali-quick-login'],
                now()->addDays($quickExpirationDays)
            )->plainTextToken,
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
        $this->refreshMobileTokenIfNeeded($request);

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

    public function updatePhoto(Request $request): JsonResponse
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:1024'],
        ]);

        $user = $request->user();
        $oldPath = $user->avatar_path;
        $path = $data['photo']->store('profile-photos', 'public');

        $user->update(['avatar_path' => $path]);
        if ($oldPath && $oldPath !== $path) {
            Storage::disk('public')->delete($oldPath);
        }

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

        // A stolen token on another device must not survive a password
        // change. Keep only the token making this request so the mobile app
        // can show success and continue the current trusted session.
        $currentTokenId = $request->user()->currentAccessToken()?->getKey();
        $user->tokens()
            ->when($currentTokenId, fn ($query) => $query->whereKeyNot($currentTokenId))
            ->delete();

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
            'photo_url' => $user->avatar_path
                ? url(Storage::disk('public')->url($user->avatar_path))
                : null,
            'must_change_password' => $user->must_change_password,
        ];
    }

    /**
     * Sliding expiration keeps a routinely used, secure-storage-backed
     * quick-login session convenient for years without making a stolen,
     * abandoned token valid forever. /me is called on app restore and every
     * PIN/biometric resume, so it is the narrowest reliable refresh point.
     */
    private function refreshMobileTokenIfNeeded(Request $request): void
    {
        $token = $request->user()->currentAccessToken();
        if (! $token instanceof PersonalAccessToken || ! $token->expires_at) {
            return;
        }

        $expirationDays = max(1, (int) config('security.wali_token_expiration_days', 30));
        $refreshWindowDays = max(
            1,
            min($expirationDays, (int) config('security.wali_token_refresh_window_days', 7)),
        );

        if ($token->expires_at->lessThanOrEqualTo(now()->addDays($refreshWindowDays))) {
            $token->forceFill(['expires_at' => now()->addDays($expirationDays)])->save();
        }
    }
}
