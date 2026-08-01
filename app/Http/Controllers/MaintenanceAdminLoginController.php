<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\MaintenanceModeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MaintenanceAdminLoginController extends Controller
{
    public function create(MaintenanceModeService $maintenance): View|RedirectResponse
    {
        if (! $maintenance->active()) {
            return redirect()->route('login');
        }

        return view('auth.maintenance-admin-login', [
            'status' => $maintenance->status(),
        ]);
    }

    public function store(Request $request, MaintenanceModeService $maintenance): RedirectResponse
    {
        if (! $maintenance->active()) {
            return redirect()->route('login');
        }

        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($credentials['login']);
        $key = 'maintenance-admin|'.Str::lower($login).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors([
                'login' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ])->onlyInput('login');
        }

        $field = str_contains($login, '@') ? 'email' : (preg_match('/^\d{16}$/', $login) ? 'no_kk' : 'nis');
        if ($field === 'no_kk' && User::where('no_kk', $login)->count() !== 1) {
            return $this->reject($request, $key);
        }

        if (! Auth::attempt([$field => $login, 'password' => $credentials['password']], false)) {
            return $this->reject($request, $key);
        }

        if (! Auth::user()->hasRole('admin')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->reject($request, $key);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put('auth.login.attempted', true);
        $request->session()->put('maintenance.admin_recovery', true);

        activity('auth')
            ->causedBy(Auth::user())
            ->withProperties(['ip' => $request->ip(), 'guard' => 'web', 'maintenance_recovery' => true])
            ->log('Login admin pemulihan maintenance berhasil');

        return redirect()->route('admin.pengaturan.maintenance');
    }

    public function destroy(Request $request, MaintenanceModeService $maintenance): RedirectResponse
    {
        abort_unless(
            $request->session()->get('maintenance.admin_recovery') === true
                && $request->user()?->hasRole('admin'),
            403,
        );

        $maintenance->deactivate($request->user());
        $request->session()->forget('maintenance.admin_recovery');

        return redirect()->route('admin.dashboard')
            ->with('success', 'Maintenance dinonaktifkan. Seluruh layanan kembali dibuka.');
    }

    private function reject(Request $request, string $key): RedirectResponse
    {
        RateLimiter::hit($key, 60);
        activity('auth')
            ->withProperties(['login' => $request->input('login'), 'ip' => $request->ip(), 'maintenance_recovery' => true])
            ->log('Percobaan login admin pemulihan maintenance gagal');

        return back()->withErrors(['login' => 'Akun admin atau kata sandi salah.'])->onlyInput('login');
    }
}
