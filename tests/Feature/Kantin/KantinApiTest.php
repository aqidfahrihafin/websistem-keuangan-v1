<?php

use App\Models\Santri;
use App\Models\Transaksi;
use App\Models\UnitUsaha;
use App\Models\WaliSantri;
use App\Services\WalletService;
use Laravel\Sanctum\Sanctum;

function makeWaliWithAnakForKantin(): array
{
    $wali = makeUserWithRole('wali');
    $santri = Santri::factory()->create();

    WaliSantri::create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'hubungan' => 'wali',
        'is_auto_generated' => false,
        'is_primary' => true,
    ]);

    $wali->update(['pin' => '135790']);

    return [$wali, $santri];
}

it('looks up an active unit usaha by kode', function () {
    [$wali] = makeWaliWithAnakForKantin();
    $unit = UnitUsaha::factory()->create(['kode' => 'KANTIN-01', 'nama' => 'Kantin Barokah']);

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson('/api/wali/unit-usaha/KANTIN-01')
        ->assertOk()
        ->assertJson(['kode' => 'KANTIN-01', 'nama' => 'Kantin Barokah']);
});

it('returns 404 for an unknown kantin kode', function () {
    [$wali] = makeWaliWithAnakForKantin();

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson('/api/wali/unit-usaha/TIDAK-ADA')->assertStatus(404);
});

it('returns 422 when looking up a nonaktif unit usaha', function () {
    [$wali] = makeWaliWithAnakForKantin();
    UnitUsaha::factory()->create(['kode' => 'KANTIN-02', 'status' => UnitUsaha::STATUS_NONAKTIF]);

    Sanctum::actingAs($wali, ['wali']);

    $this->getJson('/api/wali/unit-usaha/KANTIN-02')->assertStatus(422);
});

it('pays a kantin from santri saldo via the API', function () {
    [$wali, $santri] = makeWaliWithAnakForKantin();
    // Well above the default 100.000 minimum-saldo floor even after the
    // payment, so this test exercises the happy path, not the floor.
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI);
    $unit = UnitUsaha::factory()->create(['kode' => 'KANTIN-01', 'nama' => 'Kantin Barokah']);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/bayar-kantin", ['kode' => 'KANTIN-01', 'nominal' => 15000, 'pin' => '135790'])
        ->assertOk()
        ->assertJsonPath('unit_usaha.nama', 'Kantin Barokah')
        ->assertJsonPath('saldo_sesudah', 285000);

    expect($santri->saldo->fresh()->saldo)->toBe(285000)
        ->and($unit->fresh()->saldo_unit)->toBe(15000);
});

it('returns 422 with a machine-readable code when saldo is insufficient for a kantin payment', function () {
    [$wali, $santri] = makeWaliWithAnakForKantin();
    UnitUsaha::factory()->create(['kode' => 'KANTIN-01']);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/bayar-kantin", ['kode' => 'KANTIN-01', 'nominal' => 15000, 'pin' => '135790'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'saldo_tidak_cukup');
});

it('returns 422 with a machine-readable code when a kantin payment would drop saldo below the minimum floor', function () {
    [$wali, $santri] = makeWaliWithAnakForKantin();
    // Default floor (SaldoFloorService) is 100.000 - leaves exactly 90.000
    // after a 15.000 payment, just under it.
    app(WalletService::class)->credit($santri, 105000, Transaksi::JENIS_TOPUP_TUNAI);
    UnitUsaha::factory()->create(['kode' => 'KANTIN-01']);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/bayar-kantin", ['kode' => 'KANTIN-01', 'nominal' => 15000, 'pin' => '135790'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'saldo_di_bawah_minimum');

    expect($santri->saldo->fresh()->saldo)->toBe(105000);
});

it('rejects a kantin payment for a santri not linked to the wali', function () {
    [$wali] = makeWaliWithAnakForKantin();
    $otherSantri = Santri::factory()->create();
    UnitUsaha::factory()->create(['kode' => 'KANTIN-01']);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$otherSantri->id}/bayar-kantin", ['kode' => 'KANTIN-01', 'nominal' => 15000, 'pin' => '135790'])
        ->assertStatus(403);
});

it('returns 404 when paying with a kode that does not exist', function () {
    [$wali, $santri] = makeWaliWithAnakForKantin();
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/bayar-kantin", ['kode' => 'TIDAK-ADA', 'nominal' => 15000, 'pin' => '135790'])
        ->assertStatus(404);
});

it('includes the issued kwitansi id in a successful kantin payment response', function () {
    [$wali, $santri] = makeWaliWithAnakForKantin();
    // Well above the default 100.000 minimum-saldo floor even after the
    // payment, so the fixture itself never trips the floor check.
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI);
    UnitUsaha::factory()->create(['kode' => 'KANTIN-01']);

    Sanctum::actingAs($wali, ['wali']);

    $response = $this->postJson("/api/wali/anak/{$santri->id}/bayar-kantin", ['kode' => 'KANTIN-01', 'nominal' => 15000, 'pin' => '135790'])
        ->assertOk();

    expect($response->json('kwitansi_id'))->toBeInt();
});

it('returns 422 with a machine-readable code when a kantin payment exceeds the active daily limit', function () {
    [$wali, $santri] = makeWaliWithAnakForKantin();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);
    UnitUsaha::factory()->create(['kode' => 'KANTIN-01']);
    App\Models\KebijakanKantin::factory()->create(['limit_harian' => 10000]);

    Sanctum::actingAs($wali, ['wali']);

    $this->postJson("/api/wali/anak/{$santri->id}/bayar-kantin", ['kode' => 'KANTIN-01', 'nominal' => 15000, 'pin' => '135790'])
        ->assertStatus(422)
        ->assertJsonPath('code', 'limit_kantin_harian');
});
