<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Fallback for hosting where the control panel's cron feature can't run an
 * arbitrary shell command (only "php <this exact file path>", no chaining) -
 * an external scheduler (e.g. cron-job.org) hits these routes instead of a
 * server-side cron entry. Guarded by CRON_SECRET (not auth:sanctum/web -
 * there's no user session for an external HTTP scheduler to authenticate
 * as) so the endpoint can't be used by a stranger to spam schedule/queue
 * runs. Every route here is idempotent/safe to call redundantly - re-running
 * schedule:run before a command is actually due is a no-op, and queue:work
 * --stop-when-empty just exits immediately with nothing to do.
 */
class CronTriggerController extends Controller
{
    public function schedule(Request $request): string
    {
        $this->authorize($request);

        Artisan::call('schedule:run');

        return "OK\n".Artisan::output();
    }

    public function queue(Request $request): string
    {
        $this->authorize($request);

        Artisan::call('queue:work', [
            '--stop-when-empty' => true,
            '--max-time' => 50,
        ]);

        return "OK\n".Artisan::output();
    }

    private function authorize(Request $request): void
    {
        $secret = config('app.cron_secret');

        abort_if(blank($secret), 404);
        abort_unless(hash_equals($secret, (string) $request->route('secret')), 404);
    }
}
