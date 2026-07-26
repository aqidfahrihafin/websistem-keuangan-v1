<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Midtrans credentials can be set by an admin from the settings page
 * (stored encrypted in the settings table) or fall back to the .env
 * values - useful for first deploy before anyone has visited the page.
 */
class MidtransSettingsService
{
    public function serverKey(): ?string
    {
        return Setting::get('midtrans_server_key') ?: config('services.midtrans.server_key');
    }

    public function clientKey(): ?string
    {
        return Setting::get('midtrans_client_key') ?: config('services.midtrans.client_key');
    }

    public function isProduction(): bool
    {
        $stored = Setting::get('midtrans_is_production');

        return $stored !== null ? $stored === '1' : (bool) config('services.midtrans.is_production');
    }

    public function isConfigured(): bool
    {
        return filled($this->serverKey()) && filled($this->clientKey());
    }

    public function save(string $serverKey, string $clientKey, bool $isProduction): void
    {
        Setting::put('midtrans_server_key', $serverKey);
        Setting::put('midtrans_client_key', $clientKey);
        Setting::put('midtrans_is_production', $isProduction ? '1' : '0');
    }
}
