<?php

use App\Models\Santri;
use App\Models\Transaksi;
use App\Models\UnitUsaha;
use App\Services\KantinPembayaranService;
use App\Services\WalletService;

it('lists kantin ledger entries and filters by unit usaha', function () {
    $admin = makeUserWithRole('admin');
    $unit = UnitUsaha::factory()->create();
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);

    app(KantinPembayaranService::class)->bayar($santri, $unit, 15000, $admin);

    $this->actingAs($admin)->get(route('admin.kantin.ledger.index'))
        ->assertOk()
        ->assertSee('Pembayaran Masuk');
});
