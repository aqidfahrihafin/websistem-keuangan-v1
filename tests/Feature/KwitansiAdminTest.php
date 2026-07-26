<?php

use App\Models\Santri;
use App\Models\Transaksi;
use App\Models\UnitUsaha;
use App\Services\KantinPembayaranService;
use App\Services\WalletService;

it('lets an admin reprint a kwitansi and records who/when', function () {
    $santri = Santri::factory()->create();
    // Well above the default 100.000 minimum-saldo floor even after the
    // payment, so the fixture itself never trips the floor check.
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI);
    $unit = UnitUsaha::factory()->create();
    $admin = makeUserWithRole('admin');
    $transaksi = app(KantinPembayaranService::class)->bayar($santri, $unit, 15000, $admin);
    $kwitansi = $transaksi->kwitansi;

    expect($kwitansi->dicetak_oleh)->toBeNull();

    $staff = makeUserWithRole('bendahara');
    $this->actingAs($staff)
        ->get("/admin/kwitansi/{$kwitansi->id}/cetak")
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $kwitansi->refresh();
    expect($kwitansi->dicetak_oleh)->toBe($staff->id)
        ->and($kwitansi->dicetak_at)->not->toBeNull();
});

it('blocks a wali from reaching the admin kwitansi reprint route', function () {
    $santri = Santri::factory()->create();
    // Well above the default 100.000 minimum-saldo floor even after the
    // payment, so the fixture itself never trips the floor check.
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI);
    $unit = UnitUsaha::factory()->create();
    $admin = makeUserWithRole('admin');
    $transaksi = app(KantinPembayaranService::class)->bayar($santri, $unit, 15000, $admin);
    $kwitansi = $transaksi->kwitansi;

    $wali = makeUserWithRole('wali');
    $this->actingAs($wali)
        ->get("/admin/kwitansi/{$kwitansi->id}/cetak")
        ->assertStatus(403);
});
