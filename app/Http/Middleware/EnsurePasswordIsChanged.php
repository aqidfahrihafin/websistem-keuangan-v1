<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Locks a logged-in user to the Profil page until they change their
 * password - used for wali accounts auto-created with their No. KK as a
 * temporary password (see WaliAccountService). Livewire's own update
 * endpoint is left open so the Profil page's password form can still submit
 * (it's only ever reachable once the real page has already loaded, which
 * this middleware already gates).
 */
class EnsurePasswordIsChanged
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user
            && $user->must_change_password
            && ! $request->routeIs('profil')
            && ! $request->routeIs('logout')
            && ! $this->isLivewireRequest($request)) {
            return redirect()->route('profil')->with('status', 'Silakan ganti kata sandi Anda terlebih dahulu sebelum melanjutkan.');
        }

        return $next($request);
    }

    /**
     * Livewire's update endpoint isn't at a predictable path - it's
     * "/livewire-{8 char hash of APP_KEY}/update" (obfuscated on purpose to
     * deter scanners, see Livewire\Mechanisms\HandleRequests\EndpointResolver).
     * Matching on "livewire/*" (as if it were a fixed path) never fires,
     * which silently broke the Profil password form for any user with
     * must_change_password=true - every Livewire action request got
     * redirected instead of reaching the component, including the very
     * password-change submission meant to clear the flag. The X-Livewire
     * header the JS client always sends is a stable way to detect this
     * regardless of the path.
     */
    private function isLivewireRequest(Request $request): bool
    {
        return $request->hasHeader('X-Livewire');
    }
}
