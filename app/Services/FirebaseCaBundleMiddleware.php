<?php

namespace App\Services;

/**
 * Guzzle middleware factory (referenced from config/firebase.php's
 * guzzle_middlewares) - many local Windows PHP installs (Laragon/XAMPP) have
 * no curl.cainfo configured, which makes every outbound HTTPS request to
 * Google's OAuth2 token endpoint fail with "unable to get local issuer
 * certificate". Points Guzzle's 'verify' option at the CA bundle
 * midtrans-php already ships (same fix TopupWaliService applies to its own
 * cURL client) rather than requiring every dev machine's php.ini to be
 * edited. A no-op wherever curl.cainfo is already configured correctly
 * (e.g. most Linux hosting), since 'verify' just points at an equally
 * valid bundle either way.
 */
class FirebaseCaBundleMiddleware
{
    public function __invoke(callable $handler): callable
    {
        return function ($request, array $options) use ($handler) {
            $bundle = base_path('vendor/midtrans/midtrans-php/data/cacert.pem');

            if (is_file($bundle)) {
                $options['verify'] = $bundle;
            }

            return $handler($request, $options);
        };
    }
}
