<?php

use App\Exceptions\PinLockedException;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Models\UnitUsaha;
use App\Models\WaliSantri;
use App\Services\PinService;
use App\Services\WalletService;
use Laravel\Sanctum\Sanctum;

function makeWaliWithAnakForPin(): array
{
    $wali = makeUserWithRole('wali', ['password' => 'password']);
    $santri = Santri::factory()->create();

    WaliSantri::create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'hubungan' => 'wali',
        'is_auto_generated' => false,
        'is_primary' => true,
    ]);

    return [$wali, $santri];
}

it('reports has_pin false, then true after a pin is set', function () {
    [$wali] = makeWaliWithAnakForPin();

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson('/api/wali/pin/status')->assertOk()->assertJson(['has_pin' => false]);

    app(PinService::class)->set($wali, '246810');

    $this->getJson('/api/wali/pin/status')->assertOk()->assertJson(['has_pin' => true]);
});

it('confirms a correct account password on its own, with no side effect', function () {
    [$wali] = makeWaliWithAnakForPin();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson('/api/wali/pin/confirm-password', ['password' => 'password'])->assertOk();

    expect($wali->fresh()->hasPin())->toBeFalse();
});

it('rejects confirming the wrong account password', function () {
    [$wali] = makeWaliWithAnakForPin();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson('/api/wali/pin/confirm-password', ['password' => 'wrong-password'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('password');
});

it('sets a pin after confirming the account password', function () {
    [$wali] = makeWaliWithAnakForPin();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson('/api/wali/pin', [
        'current_password' => 'password',
        'pin' => '246810',
        'pin_confirmation' => '246810',
    ])->assertOk();

    expect($wali->fresh()->hasPin())->toBeTrue();
});

it('rejects setting a pin with the wrong current password', function () {
    [$wali] = makeWaliWithAnakForPin();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson('/api/wali/pin', [
        'current_password' => 'wrong-password',
        'pin' => '246810',
        'pin_confirmation' => '246810',
    ])->assertStatus(422)->assertJsonValidationErrors('current_password');

    expect($wali->fresh()->hasPin())->toBeFalse();
});

it('rejects setting a pin when the confirmation does not match', function () {
    [$wali] = makeWaliWithAnakForPin();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson('/api/wali/pin', [
        'current_password' => 'password',
        'pin' => '246810',
        'pin_confirmation' => '000000',
    ])->assertStatus(422)->assertJsonValidationErrors('pin');
});

it('PinService verifies a correct pin and rejects a wrong one', function () {
    [$wali] = makeWaliWithAnakForPin();
    app(PinService::class)->set($wali, '246810');

    expect(app(PinService::class)->verify($wali, '246810'))->toBeTrue()
        ->and(app(PinService::class)->verify($wali, '000000'))->toBeFalse();
});

it('locks PIN verification out after too many wrong attempts', function () {
    [$wali] = makeWaliWithAnakForPin();
    app(PinService::class)->set($wali, '246810');
    $service = app(PinService::class);

    foreach (range(1, 5) as $attempt) {
        expect($service->verify($wali, '000000'))->toBeFalse();
    }

    $service->verify($wali, '246810');
})->throws(PinLockedException::class);

it('rejects a kantin payment when the pin field is missing', function () {
    [$wali, $santri] = makeWaliWithAnakForPin();
    app(PinService::class)->set($wali, '246810');
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    UnitUsaha::factory()->create(['kode' => 'KANTIN-01']);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/bayar-kantin", ['kode' => 'KANTIN-01', 'nominal' => 15000])
        ->assertStatus(422)
        ->assertJsonValidationErrors('pin');
});

it('rejects a kantin payment with the wrong pin', function () {
    [$wali, $santri] = makeWaliWithAnakForPin();
    app(PinService::class)->set($wali, '246810');
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    UnitUsaha::factory()->create(['kode' => 'KANTIN-01']);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/bayar-kantin", ['kode' => 'KANTIN-01', 'nominal' => 15000, 'pin' => '000000'])
        ->assertStatus(422);

    expect($santri->saldo->fresh()->saldo)->toBe(100000);
});

it('rejects a kantin payment when the wali has not set a pin yet', function () {
    [$wali, $santri] = makeWaliWithAnakForPin();
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    UnitUsaha::factory()->create(['kode' => 'KANTIN-01']);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/bayar-kantin", ['kode' => 'KANTIN-01', 'nominal' => 15000, 'pin' => '246810'])
        ->assertStatus(422);
});

it('allows a kantin payment through with the correct pin', function () {
    [$wali, $santri] = makeWaliWithAnakForPin();
    app(PinService::class)->set($wali, '246810');
    // Well above the default 100.000 minimum-saldo floor even after the
    // payment, so this test exercises the happy path, not the floor.
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI);
    UnitUsaha::factory()->create(['kode' => 'KANTIN-01']);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/bayar-kantin", ['kode' => 'KANTIN-01', 'nominal' => 15000, 'pin' => '246810'])
        ->assertOk();

    expect($santri->saldo->fresh()->saldo)->toBe(285000);
});

it('returns 423 once pin attempts are locked out on a real gated endpoint', function () {
    [$wali, $santri] = makeWaliWithAnakForPin();
    app(PinService::class)->set($wali, '246810');
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    UnitUsaha::factory()->create(['kode' => 'KANTIN-01']);

    Sanctum::actingAs($wali, ['wali']);

    foreach (range(1, 5) as $attempt) {
        $this->postJson("/api/wali/anak/{$santri->id}/bayar-kantin", ['kode' => 'KANTIN-01', 'nominal' => 15000, 'pin' => '000000'])
            ->assertStatus(422);
    }

    $this->postJson("/api/wali/anak/{$santri->id}/bayar-kantin", ['kode' => 'KANTIN-01', 'nominal' => 15000, 'pin' => '246810'])
        ->assertStatus(423);
});
