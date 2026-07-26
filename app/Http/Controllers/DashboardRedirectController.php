<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DashboardRedirectController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = Auth::user();

        return match (true) {
            $user->hasAnyRole(['admin', 'bendahara']) => redirect()->route('admin.dashboard'),
            $user->hasRole('pengasuh') => redirect()->route('pengasuh.dashboard'),
            $user->hasRole('wali') => redirect()->route('wali.dashboard'),
            $user->hasRole('santri') => redirect()->route('santri.dashboard'),
            $user->hasRole('pengelola') => redirect()->route('pengelola.dashboard'),
            $user->hasRole('dev') => redirect()->route('dev.dashboard'),
            default => redirect()->route('login'),
        };
    }
}
