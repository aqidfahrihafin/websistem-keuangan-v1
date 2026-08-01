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

        if ($request->user()?->hasRole('admin') !== true) {
            return false;
        }

        if ($request->is('admin/pengaturan/maintenance', 'logout')) {
            return true;
        }

        // Livewire actions use a shared /livewire/update endpoint. Inspect
        // the signed component snapshot so only the maintenance controller,
        // not other admin financial screens, remains operable.
        if ($request->is('livewire/update')) {
            foreach ((array) $request->input('components', []) as $component) {
                $snapshot = json_decode((string) ($component['snapshot'] ?? ''), true);
                if (($snapshot['memo']['name'] ?? null) === 'admin.pengaturan.maintenance') {
                    return true;
                }
            }
        }

        return false;
    }
}
