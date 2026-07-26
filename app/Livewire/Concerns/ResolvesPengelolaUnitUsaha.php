<?php

namespace App\Livewire\Concerns;

use App\Models\UnitUsaha;
use Illuminate\Support\Facades\Auth;

/**
 * Every Pengelola\* component uses this instead of accepting any
 * client-supplied unit_usaha id - a pengelola account manages at most one
 * UnitUsaha (User::unitUsahaDikelola), so scope is always resolved
 * server-side from the logged-in user, never trusted from the request.
 */
trait ResolvesPengelolaUnitUsaha
{
    protected function unitUsaha(): UnitUsaha
    {
        return Auth::user()->unitUsahaDikelola ?? abort(403);
    }
}
