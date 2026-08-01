<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\PersonalAccessToken;

it('adds baseline browser security headers', function () {
    $this->get('/login')
        ->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('attaches named throttles to expensive and public endpoints', function () {
    expect(Route::getRoutes()->getByName('api.wali.app-info')->gatherMiddleware())
        ->toContain('throttle:public-api')
        ->and(Route::getRoutes()->getByName('api.wali.anak.topup.store-core')->gatherMiddleware())
        ->toContain('throttle:api', 'throttle:financial')
        ->and(Route::getRoutes()->getByName('api.wali.topup.sync')->gatherMiddleware())
        ->toContain('throttle:api', 'throttle:payment-sync')
        ->and(Route::getRoutes()->getByName('midtrans.webhook')->gatherMiddleware())
        ->toContain('throttle:webhook');
});

it('issues expiring tokens to wali mobile clients', function () {
    config(['security.wali_token_expiration_days' => 30]);
    makeUserWithRole('wali', [
        'email' => 'expiring-token@test.com',
        'password' => 'password',
    ]);

    $before = now()->addDays(30)->subMinute();

    $this->postJson('/api/wali/login', [
        'login' => 'expiring-token@test.com',
        'password' => 'password',
        'device_name' => 'security-test',
    ])->assertOk();

    $token = PersonalAccessToken::query()->latest('id')->firstOrFail();

    expect($token->expires_at)->not->toBeNull()
        ->and($token->expires_at->between($before, now()->addDays(30)->addMinute()))->toBeTrue();
});

it('revokes other mobile sessions after a password change', function () {
    /** @var User $wali */
    $wali = makeUserWithRole('wali', [
        'email' => 'password-revoke@test.com',
        'password' => Hash::make('sandi-lama'),
    ]);
    $oldToken = $wali->createToken('old-phone', ['wali'], now()->addDays(30));
    $currentToken = $wali->createToken('current-phone', ['wali'], now()->addDays(30));

    $this->withHeader('Authorization', 'Bearer '.$currentToken->plainTextToken)
        ->postJson('/api/wali/password', [
            'current_password' => 'sandi-lama',
            'password' => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
        ])
        ->assertOk();

    expect(PersonalAccessToken::find($oldToken->accessToken->id))->toBeNull()
        ->and(PersonalAccessToken::find($currentToken->accessToken->id))->not->toBeNull();
});

it('extends a wali token near expiry when quick login validates the session', function () {
    config([
        'security.wali_token_expiration_days' => 30,
        'security.wali_token_refresh_window_days' => 7,
    ]);
    $wali = makeUserWithRole('wali');
    $token = $wali->createToken('active-phone', ['wali'], now()->addDays(2));

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->getJson('/api/wali/me')
        ->assertOk();

    expect($token->accessToken->fresh()->expires_at)
        ->toBeGreaterThan(now()->addDays(29));
});
