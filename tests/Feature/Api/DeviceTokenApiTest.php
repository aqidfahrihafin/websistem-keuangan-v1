<?php

use App\Models\WaliDeviceToken;
use Laravel\Sanctum\Sanctum;

it('registers a device token for the authenticated wali', function () {
    $wali = makeUserWithRole('wali');
    Sanctum::actingAs($wali, ['wali']);

    $this->postJson('/api/wali/device-token', ['fcm_token' => 'token-abc'])->assertOk();

    $token = WaliDeviceToken::where('fcm_token', 'token-abc')->first();
    expect($token)->not->toBeNull()
        ->and($token->user_id)->toBe($wali->id)
        ->and($token->platform)->toBe('android');
});

it('re-registering the same fcm_token repoints it to whoever is currently signed in', function () {
    $waliLama = makeUserWithRole('wali');
    $waliBaru = makeUserWithRole('wali');

    WaliDeviceToken::create(['user_id' => $waliLama->id, 'fcm_token' => 'shared-device', 'platform' => 'android']);

    Sanctum::actingAs($waliBaru, ['wali']);
    $this->postJson('/api/wali/device-token', ['fcm_token' => 'shared-device'])->assertOk();

    expect(WaliDeviceToken::where('fcm_token', 'shared-device')->count())->toBe(1)
        ->and(WaliDeviceToken::where('fcm_token', 'shared-device')->first()->user_id)->toBe($waliBaru->id);
});

it('rejects device token registration without a bearer token', function () {
    $this->postJson('/api/wali/device-token', ['fcm_token' => 'token-abc'])->assertStatus(401);
});

it('validates fcm_token is required to register a device token', function () {
    $wali = makeUserWithRole('wali');
    Sanctum::actingAs($wali, ['wali']);

    $this->postJson('/api/wali/device-token', [])->assertStatus(422);
});

it('deletes the device token for the authenticated wali on logout', function () {
    $wali = makeUserWithRole('wali');
    WaliDeviceToken::create(['user_id' => $wali->id, 'fcm_token' => 'token-to-remove', 'platform' => 'android']);

    Sanctum::actingAs($wali, ['wali']);
    $this->deleteJson('/api/wali/device-token', ['fcm_token' => 'token-to-remove'])->assertOk();

    expect(WaliDeviceToken::where('fcm_token', 'token-to-remove')->exists())->toBeFalse();
});

it('does not let a wali delete another wali device token', function () {
    $pemilik = makeUserWithRole('wali');
    $lain = makeUserWithRole('wali');
    WaliDeviceToken::create(['user_id' => $pemilik->id, 'fcm_token' => 'token-milik-orang-lain', 'platform' => 'android']);

    Sanctum::actingAs($lain, ['wali']);
    $this->deleteJson('/api/wali/device-token', ['fcm_token' => 'token-milik-orang-lain'])->assertOk();

    expect(WaliDeviceToken::where('fcm_token', 'token-milik-orang-lain')->exists())->toBeTrue();
});
