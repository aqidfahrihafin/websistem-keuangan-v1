<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\MaintenanceModeService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::guest')]
class LoginForm extends Component
{
    public string $login = '';

    public string $password = '';

    public bool $remember = false;

    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function submit(MaintenanceModeService $maintenance): void
    {
        $this->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($this->login);

        // Livewire actions bypass route middleware entirely (they go
        // through /livewire/update, not this page's own route), so
        // throttling has to happen here rather than via a `throttle:`
        // route middleware - same reasoning as PinService's RateLimiter
        // use. Keyed by login+IP (mirrors Laravel's own built-in
        // ThrottlesLogins trait) so one attacker can't lock out a real
        // wali by spamming wrong passwords against their identifier from
        // a different IP.
        $throttleKey = Str::lower($login).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('login', "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.");

            return;
        }

        if (str_contains($login, '@')) {
            $field = 'email';
        } elseif (preg_match('/^\d{16}$/', $login)) {
            $field = 'no_kk';

            // A No. KK can legitimately be shared by more than one wali
            // account (see WaliAccountService) - only usable as a login
            // identifier while it still points at exactly one account,
            // otherwise which one would even be logged in is ambiguous.
            if (User::where('no_kk', $login)->count() !== 1) {
                RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
                activity('auth')->withProperties(['login' => $login, 'ip' => request()->ip(), 'guard' => 'web'])->log('Percobaan login gagal');
                $this->addError('login', 'Login atau kata sandi salah.');

                return;
            }
        } else {
            $field = 'nis';
        }

        if (! Auth::attempt([$field => $login, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            activity('auth')->withProperties(['login' => $login, 'ip' => request()->ip(), 'guard' => 'web'])->log('Percobaan login gagal');
            $this->addError('login', 'Login atau kata sandi salah.');

            return;
        }

        if ($maintenance->active() && ! Auth::user()->hasRole('admin')) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            RateLimiter::clear($throttleKey);
            $this->addError('login', 'Sistem sedang dalam maintenance. Hanya admin pemulihan yang dapat masuk.');

            return;
        }

        RateLimiter::clear($throttleKey);
        activity('auth')->causedBy(Auth::user())->withProperties(['ip' => request()->ip(), 'guard' => 'web'])->log('Login berhasil');

        session()->regenerate();
        session()->put('auth.login.attempted', true);

        if (request()->isSecure()) {
            config(['session.secure' => true]);
        }

        $destination = $maintenance->active()
            ? route('admin.pengaturan.maintenance')
            : route('dashboard');

        $this->redirect($destination, navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.login-form');
    }
}
