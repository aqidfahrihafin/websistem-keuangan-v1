<?php

use App\Models\Keluarga;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Models\WaliSantri;
use App\Services\PinService;
use App\Services\WalletService;
use Laravel\Sanctum\Sanctum;

/**
 * @return array{0: \App\Models\User, 1: Santri, 2: Santri} wali linked only
 * to $kakak, plus $kakak and $adik sharing the same keluarga
 */
function makeWaliWithSiblings(): array
{
    $keluarga = Keluarga::factory()->create();
    $kakak = Santri::factory()->create(['keluarga_id' => $keluarga->id]);
    $adik = Santri::factory()->create(['keluarga_id' => $keluarga->id]);

    $wali = makeUserWithRole('wali', ['password' => 'password']);
    WaliSantri::create([
        'user_id' => $wali->id,
        'santri_id' => $kakak->id,
        'hubungan' => 'wali',
        'is_auto_generated' => false,
        'is_primary' => true,
    ]);

    app(PinService::class)->set($wali, '246810');

    return [$wali, $kakak, $adik];
}

it('transfers saldo between two santri in the same keluarga', function () {
    [$wali, $kakak, $adik] = makeWaliWithSiblings();
    // Well above the default 100.000 minimum-saldo floor even after the
    // transfer, so this test exercises the happy path, not the floor.
    app(WalletService::class)->credit($kakak, 300000, Transaksi::JENIS_TOPUP_TUNAI);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$kakak->id}/transfer", [
        'ke_santri_id' => $adik->id,
        'nominal' => 30000,
        'pin' => '246810',
    ])
        ->assertOk()
        ->assertJsonPath('dari.saldo_sesudah', 270000)
        ->assertJsonPath('ke.saldo_sesudah', 30000);

    expect($kakak->saldo->fresh()->saldo)->toBe(270000)
        ->and($adik->saldo->fresh()->saldo)->toBe(30000);

    $debit = Transaksi::where('santri_id', $kakak->id)->where('jenis', 'transfer_antar_santri')->sole();
    $credit = Transaksi::where('santri_id', $adik->id)->where('jenis', 'transfer_antar_santri')->sole();

    expect($debit->arah)->toBe(Transaksi::ARAH_DEBIT)
        ->and($debit->referensi_type)->toBe(Santri::class)
        ->and($debit->referensi_id)->toBe($adik->id)
        ->and($credit->arah)->toBe(Transaksi::ARAH_KREDIT)
        ->and($credit->referensi_id)->toBe($kakak->id);
});

it('rejects a transfer to a santri in a different keluarga', function () {
    [$wali, $kakak] = makeWaliWithSiblings();
    $luar = Santri::factory()->create(['keluarga_id' => Keluarga::factory()->create()->id]);
    app(WalletService::class)->credit($kakak, 100000, Transaksi::JENIS_TOPUP_TUNAI);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$kakak->id}/transfer", [
        'ke_santri_id' => $luar->id,
        'nominal' => 30000,
        'pin' => '246810',
    ])->assertStatus(422);

    expect($kakak->saldo->fresh()->saldo)->toBe(100000);
});

it('rejects transferring a santri to themselves', function () {
    [$wali, $kakak] = makeWaliWithSiblings();
    app(WalletService::class)->credit($kakak, 100000, Transaksi::JENIS_TOPUP_TUNAI);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$kakak->id}/transfer", [
        'ke_santri_id' => $kakak->id,
        'nominal' => 30000,
        'pin' => '246810',
    ])->assertStatus(422);
});

it('rejects a transfer that would drop the sender below the minimum saldo floor', function () {
    [$wali, $kakak, $adik] = makeWaliWithSiblings();
    // Default floor (SaldoFloorService) is 100.000 - leaves exactly 90.000
    // after a 30.000 transfer, just under it.
    app(WalletService::class)->credit($kakak, 120000, Transaksi::JENIS_TOPUP_TUNAI);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$kakak->id}/transfer", [
        'ke_santri_id' => $adik->id,
        'nominal' => 30000,
        'pin' => '246810',
    ])->assertStatus(422)->assertJsonPath('code', 'saldo_di_bawah_minimum');

    expect($kakak->saldo->fresh()->saldo)->toBe(120000)
        ->and($adik->saldo->fresh()->saldo)->toBe(0);
});

it('allows a transfer that lands exactly on the minimum saldo floor', function () {
    [$wali, $kakak, $adik] = makeWaliWithSiblings();
    app(WalletService::class)->credit($kakak, 130000, Transaksi::JENIS_TOPUP_TUNAI);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$kakak->id}/transfer", [
        'ke_santri_id' => $adik->id,
        'nominal' => 30000,
        'pin' => '246810',
    ])->assertOk();

    expect($kakak->saldo->fresh()->saldo)->toBe(100000);
});

it('rejects a transfer when the sender has insufficient saldo', function () {
    [$wali, $kakak, $adik] = makeWaliWithSiblings();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$kakak->id}/transfer", [
        'ke_santri_id' => $adik->id,
        'nominal' => 30000,
        'pin' => '246810',
    ])->assertStatus(422)->assertJsonPath('code', 'saldo_tidak_cukup');
});

it('rejects a transfer to an inactive sibling', function () {
    [$wali, $kakak, $adik] = makeWaliWithSiblings();
    $adik->update(['status' => Santri::STATUS_NONAKTIF]);
    app(WalletService::class)->credit($kakak, 100000, Transaksi::JENIS_TOPUP_TUNAI);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$kakak->id}/transfer", [
        'ke_santri_id' => $adik->id,
        'nominal' => 30000,
        'pin' => '246810',
    ])->assertStatus(422);
});

it('rejects a transfer from a santri not linked to the wali', function () {
    [$wali] = makeWaliWithSiblings();
    $other = Santri::factory()->create();

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$other->id}/transfer", [
        'ke_santri_id' => $other->id,
        'nominal' => 30000,
        'pin' => '246810',
    ])->assertStatus(403);
});

it('rejects a transfer with the wrong pin', function () {
    [$wali, $kakak, $adik] = makeWaliWithSiblings();
    app(WalletService::class)->credit($kakak, 100000, Transaksi::JENIS_TOPUP_TUNAI);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$kakak->id}/transfer", [
        'ke_santri_id' => $adik->id,
        'nominal' => 30000,
        'pin' => '000000',
    ])->assertStatus(422);

    expect($kakak->saldo->fresh()->saldo)->toBe(100000);
});

it('lists only active siblings sharing the same keluarga', function () {
    [$wali, $kakak, $adik] = makeWaliWithSiblings();
    $nonaktifAdik = Santri::factory()->create(['keluarga_id' => $kakak->keluarga_id, 'status' => Santri::STATUS_NONAKTIF]);
    $luar = Santri::factory()->create(['keluarga_id' => Keluarga::factory()->create()->id]);

    Sanctum::actingAs($wali, ['wali']);

    $ids = collect($this->getJson("/api/wali/anak/{$kakak->id}/saudara")->assertOk()->json('data'))->pluck('id');

    expect($ids)->toContain($adik->id)
        ->not->toContain($kakak->id)
        ->not->toContain($nonaktifAdik->id)
        ->not->toContain($luar->id);
});

it('rejects listing siblings for a santri not linked to the wali', function () {
    [$wali] = makeWaliWithSiblings();
    $other = Santri::factory()->create();

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson("/api/wali/anak/{$other->id}/saudara")->assertStatus(403);
});
