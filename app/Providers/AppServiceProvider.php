<?php

namespace App\Providers;

use App\Models\Santri;
use App\Models\User;
use App\Observers\SantriObserver;
use App\Observers\UserObserver;
use App\Services\FingerprintVerifier;
use App\Services\TrustedDeviceFingerprintVerifier;
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

        // Generated URLs (assets, route(), redirects) must be https:// in
        // production even if the request somehow reaches PHP over plain
        // http (e.g. a misconfigured reverse proxy step) - mixed-content
        // http asset URLs would otherwise get silently blocked by browsers
        // on a page served over https.
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
