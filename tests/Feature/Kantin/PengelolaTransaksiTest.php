<?php

use App\Livewire\Pengelola\Transaksi as PengelolaTransaksi;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Services\KantinPembayaranService;
use App\Services\WalletService;
use Livewire\Livewire;

it('shows only transactions belonging to the logged in pengelola unit', function () {
    [$unitA, $pengelolaA] = buatPengelola();
    [$unitB] = buatPengelola();
    $santri = Santri::factory()->create();
    // Keep the fixture above the production minimum-saldo floor after both
    // payments; this test is about unit scoping, not saldo policy.
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);

    app(KantinPembayaranService::class)->bayar($santri, $unitA, 11000, null);
    app(KantinPembayaranService::class)->bayar($santri, $unitB, 22000, null);

    $this->actingAs($pengelolaA);

    Livewire::test(PengelolaTransaksi::class)
        ->assertSee('11.000')
        ->assertDontSee('22.000')
        ->assertSee($santri->nama);
});

it('allows an owner to print their own receipt and rejects another owner receipt', function () {
    [$unitA, $pengelolaA] = buatPengelola();
    [$unitB] = buatPengelola();
    $santri = Santri::factory()->create();
    // Keep the fixture above the production minimum-saldo floor after both
    // payments; this test is about receipt ownership, not saldo policy.
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);

    $own = app(KantinPembayaranService::class)->bayar($santri, $unitA, 10000, null)->kwitansi;
    $other = app(KantinPembayaranService::class)->bayar($santri, $unitB, 10000, null)->kwitansi;

    $this->actingAs($pengelolaA)
        ->get("/pengelola/kwitansi/{$own->id}")
        ->assertOk();

    $this->actingAs($pengelolaA)
        ->get("/pengelola/kwitansi/{$other->id}")
        ->assertForbidden();
});
