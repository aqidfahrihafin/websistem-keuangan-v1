<?php

namespace App\Providers;

use App\Models\Santri;
use App\Models\User;
use App\Observers\SantriObserver;
use App\Observers\UserObserver;
use App\Services\FingerprintVerifier;
use App\Services\TrustedDeviceFingerprintVerifier;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(FingerprintVerifier::class, TrustedDeviceFingerprintVerifier::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        User::observe(UserObserver::class);
        Santri::observe(SantriObserver::class);

        $this->configureRateLimiting();

        // Generated URLs (assets, route(), redirects) must be https:// in
        // production even if the request somehow reaches PHP over plain
        // http (e.g. a misconfigured reverse proxy step) - mixed-content
        // http asset URLs would otherwise get silently blocked by browsers
        // on a page served over https.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            $actor = $request->user();
            $key = $actor
                ? $actor::class.':'.$actor->getAuthIdentifier()
                : $request->ip();

            return Limit::perMinute((int) config('security.rate_limits.api', 120))
                ->by($key);
        });

        RateLimiter::for('public-api', fn (Request $request) => Limit::perMinute(
            (int) config('security.rate_limits.public', 60)
        )->by($request->ip()));

        RateLimiter::for('auth-sensitive', fn (Request $request) => Limit::perMinute(
            (int) config('security.rate_limits.auth_sensitive', 10)
        )->by(($request->user()?->getAuthIdentifier() ?? 'guest').'|'.$request->ip()));

        RateLimiter::for('financial', fn (Request $request) => Limit::perMinute(
            (int) config('security.rate_limits.financial', 10)
        )->by(($request->user()?->getAuthIdentifier() ?? 'guest').'|'.$request->ip()));

        RateLimiter::for('payment-sync', fn (Request $request) => Limit::perMinute(
            (int) config('security.rate_limits.payment_sync', 5)
        )->by(($request->user()?->getAuthIdentifier() ?? 'guest').'|'.$request->ip()));

        RateLimiter::for('webhook', fn (Request $request) => Limit::perMinute(
            (int) config('security.rate_limits.webhook', 120)
        )->by($request->ip()));
    }
}
