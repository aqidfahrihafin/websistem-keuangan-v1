<?php

namespace App\Http\Middleware;

use App\Services\MaintenanceModeService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RejectRequestsDuringOperationalMaintenance
{
    public function __construct(private readonly MaintenanceModeService $maintenance) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->maintenance->active() || $this->mayPass($request)) {
            return $next($request);
        }

        $status = $this->maintenance->publicStatus();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => $status['message'],
                'code' => 'maintenance_mode',
                'maintenance' => $status,
            ], 503, ['Retry-After' => '60']);
        }

        return response()->view('errors.maintenance', $status, 503, ['Retry-After' => '60']);
    }

    private function mayPass(Request $request): bool
    {
        if ($request->is('api/system/status') || $request->is('up')) {
            return true;
        }

        // Login stays reachable as a recovery entrance. LoginForm itself
        // only retains an authenticated session for the admin role while
        // maintenance is active.
        if ($request->is('login')) {
            return true;
        }

        if ($request->is('livewire/update') && $this->isLivewireComponent($request, 'auth.login-form')) {
            return true;
        }

        // Set only after a successful admin login while maintenance is
        // active. This remains reliable on hosting even when the role
        // resolver is not yet warm for the first redirected request.
        $recoverySession = $request->hasSession()
            && $request->session()->get('maintenance.admin_recovery') === true;
        if ($recoverySession && $request->is('admin/pengaturan/maintenance')) {
            return true;
        }

        if ($recoverySession && $request->is('livewire/update') && $this->isLivewireComponent($request, 'admin.pengaturan.maintenance')) {
            return true;
        }

        if ($request->user()?->hasRole('admin') !== true) {
            return false;
        }

        if ($request->is('admin/pengaturan/maintenance', 'logout')) {
            return true;
        }

        // Livewire actions use a shared /livewire/update endpoint. Inspect
        // the signed component snapshot so only the maintenance controller,
        // not other admin financial screens, remains operable.
        if ($request->is('livewire/update') && $this->isLivewireComponent($request, 'admin.pengaturan.maintenance')) {
            return true;
        }

        return false;
    }

    private function isLivewireComponent(Request $request, string $name): bool
    {
        foreach ((array) $request->input('components', []) as $component) {
            $snapshot = json_decode((string) ($component['snapshot'] ?? ''), true);
            if (($snapshot['memo']['name'] ?? null) === $name) {
                return true;
            }
        }

        return false;
    }
}
