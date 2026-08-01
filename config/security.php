<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application-layer rate limits
    |--------------------------------------------------------------------------
    |
    | These protect PHP and downstream services from accidental or
    | authenticated abuse. A reverse proxy/WAF is still required to absorb
    | volumetric DDoS before traffic reaches this application.
    |
    */
    'rate_limits' => [
        'api' => (int) env('RATE_LIMIT_API', 120),
        'public' => (int) env('RATE_LIMIT_PUBLIC', 60),
        'auth_sensitive' => (int) env('RATE_LIMIT_AUTH_SENSITIVE', 10),
        'financial' => (int) env('RATE_LIMIT_FINANCIAL', 10),
        'payment_sync' => (int) env('RATE_LIMIT_PAYMENT_SYNC', 5),
        'webhook' => (int) env('RATE_LIMIT_WEBHOOK', 120),
    ],

    /*
    | Wali tokens expire while long-lived kiosk tokens remain unaffected.
    | Set to 0 only for a deliberate, documented emergency rollback.
    */
    'wali_token_expiration_days' => (int) env('WALI_TOKEN_EXPIRATION_DAYS', 30),
    'wali_token_refresh_window_days' => (int) env('WALI_TOKEN_REFRESH_WINDOW_DAYS', 7),
    'wali_quick_token_expiration_days' => (int) env('WALI_QUICK_TOKEN_EXPIRATION_DAYS', 365),
];
