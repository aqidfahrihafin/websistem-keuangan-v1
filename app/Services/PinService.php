<?php

namespace App\Services;

use App\Exceptions\PinLockedException;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * The transaction PIN is a second factor gating sensitive wali-initiated
 * mobile actions (kantin payment, tagihan-from-saldo, transfer antar
 * santri) - separate from the account password, since holding an unlocked
 * phone is otherwise the only barrier to moving money. Attempt lockout
 * mirrors Kios\CekSaldo's RateLimiter pattern (no DB attempt column).
 */
class PinService
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 900;

    public function set(User $user, string $pin): void
    {
        $user->update(['pin' => $pin, 'pin_set_at' => now()]);
    }

    /**
     * @throws PinLockedException if the account is currently locked out
     */
    public function verify(User $user, string $pin): bool
    {
        $key = $this->rateLimitKey($user);

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw new PinLockedException('Terlalu banyak percobaan PIN salah. Coba lagi dalam 15 menit.');
        }

        if ($user->pin === null || ! Hash::check($pin, $user->pin)) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            return false;
        }

        RateLimiter::clear($key);

        return true;
    }

    private function rateLimitKey(User $user): string
    {
        return 'wali-pin:'.$user->id;
    }
}
