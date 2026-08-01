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
            $user->hasAnyRole(['superadmin', 'admin', 'bendahara']) => redirect()->route('admin.dashboard'),
            $user->hasRole('petugas_kios') => redirect()->route('petugas-kios.dashboard'),
            $user->hasRole('pengasuh') => redirect()->route('pengasuh.dashboard'),
            $user->hasAnyRole(['admin_lembaga', 'admin_rayon']) => redirect()->route('unit.dashboard'),
            $user->hasRole('wali') => redirect()->route('wali.dashboard'),
            $user->hasRole('santri') => redirect()->route('santri.dashboard'),
            $user->hasRole('pengelola') => redirect()->route('pengelola.dashboard'),
            $user->hasRole('dev') => redirect()->route('dev.dashboard'),
            default => redirect()->route('login'),
        };
    }
}
