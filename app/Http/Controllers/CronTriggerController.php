<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

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
    public function schedule(Request $request): JsonResponse
    {
        $this->authorize($request);

        return $this->run('schedule:run');
    }

    public function queue(Request $request): JsonResponse
    {
        $this->authorize($request);

        return $this->run('queue:work', [
            '--stop-when-empty' => true,
            '--max-time' => 20,
            '--sleep' => 1,
            '--tries' => 3,
        ]);
    }

    private function authorize(Request $request): void
    {
        $secret = config('app.cron_secret');

        abort_if(blank($secret), 404);
        abort_unless(hash_equals($secret, (string) $request->route('secret')), 404);
    }

    /**
     * Return a non-2xx response when Artisan fails so hosting/external cron
     * monitors can detect the problem instead of recording a false success.
     *
     * @param  array<string, mixed>  $parameters
     */
    private function run(string $command, array $parameters = []): JsonResponse
    {
        try {
            $exitCode = Artisan::call($command, $parameters);
            $output = trim(Artisan::output());

            if ($exitCode !== 0) {
                Log::error('Cron command failed.', compact('command', 'exitCode', 'output'));

                return response()->json([
                    'ok' => false,
                    'command' => $command,
                    'message' => 'Perintah cron gagal. Periksa storage/logs/laravel.log.',
                ], 500);
            }

            return response()->json([
                'ok' => true,
                'command' => $command,
                'message' => $output !== '' ? $output : 'Selesai.',
                'ran_at' => now()->toIso8601String(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Cron command threw an exception.', [
                'command' => $command,
                'exception' => $exception,
            ]);

            return response()->json([
                'ok' => false,
                'command' => $command,
                'message' => 'Cron tidak dapat dijalankan. Periksa konfigurasi hosting dan log aplikasi.',
            ], 500);
        }
    }
}
