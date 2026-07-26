<?php

use App\Models\Santri;
use App\Models\Transaksi;
use App\Models\UnitUsaha;
use App\Models\WaliSantri;
use App\Services\KantinPembayaranService;
use App\Services\WalletService;
use Laravel\Sanctum\Sanctum;

function makeWaliWithKwitansi(): array
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

    // Well above the default 100.000 minimum-saldo floor even after the
    // payment, so the fixture itself never trips the floor check.
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI);
    $unit = UnitUsaha::factory()->create();
    $admin = makeUserWithRole('admin');
    $transaksi = app(KantinPembayaranService::class)->bayar($santri, $unit, 15000, $admin);
    $kwitansi = $transaksi->kwitansi;

    return [$wali, $santri, $kwitansi];
}

it('returns a signed pdf url for a kwitansi belonging to the wali own santri', function () {
    [$wali, , $kwitansi] = makeWaliWithKwitansi();

    Sanctum::actingAs($wali, ['wali']);

    $response = $this->getJson("/api/wali/kwitansi/{$kwitansi->id}")
        ->assertOk()
        ->assertJsonPath('nomor_kwitansi', $kwitansi->nomor_kwitansi);

    $url = $response->json('pdf_url');
    expect($url)->toContain("/kwitansi/{$kwitansi->id}/pdf")
        ->and($url)->toContain('signature=');
});

it('rejects a wali requesting a kwitansi for a santri not linked to them', function () {
    [, , $kwitansi] = makeWaliWithKwitansi();
    $otherWali = makeUserWithRole('wali');

    Sanctum::actingAs($otherWali, ['wali']);

    $this->getJson("/api/wali/kwitansi/{$kwitansi->id}")->assertStatus(403);
});

it('serves the actual pdf from a valid signed link', function () {
    [$wali, , $kwitansi] = makeWaliWithKwitansi();

    Sanctum::actingAs($wali, ['wali']);
    $url = $this->getJson("/api/wali/kwitansi/{$kwitansi->id}")->json('pdf_url');

    // The signed route itself needs no auth at all - the signature is the
    // authorization, so this deliberately does NOT reuse Sanctum::actingAs.
    $this->get($url)
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('rejects the signed pdf link once tampered with', function () {
    [$wali, , $kwitansi] = makeWaliWithKwitansi();

    Sanctum::actingAs($wali, ['wali']);
    $url = $this->getJson("/api/wali/kwitansi/{$kwitansi->id}")->json('pdf_url');

    $this->get($url.'tampered')->assertStatus(403);
});
