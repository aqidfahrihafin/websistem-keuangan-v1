<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

it('returns app branding with no auth required, defaults when nothing is configured', function () {
    $this->getJson('/api/wali/app-info')
        ->assertOk()
        ->assertJson([
            'nama_aplikasi' => 'Sistem Keuangan Santri',
            'nama_pondok' => 'Pondok Pesantren Latee (Annuqayah)',
            'logo_url' => null,
        ]);
});

it('returns the configured name and logo url once set', function () {
    Storage::fake('public');
    Storage::disk('public')->put('logo/test.png', 'fake-bytes');
    Setting::put('app_nama_aplikasi', 'App Kantin Latee');
    Setting::put('app_logo_path', 'logo/test.png');

    $response = $this->getJson('/api/wali/app-info')
        ->assertOk()
        ->assertJson(['nama_aplikasi' => 'App Kantin Latee']);

    expect($response->json('logo_url'))->not->toBeNull();
});
