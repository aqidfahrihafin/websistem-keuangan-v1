<?php

use App\Exceptions\LimitKantinHarianException;
use App\Models\KebijakanKantin;
use App\Models\Kwitansi;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Models\UnitUsaha;
use App\Services\KantinPembayaranService;
use App\Services\WalletService;

it('debits the paying santri and credits the unit usaha atomically', function () {
    $santri = Santri::factory()->create();
    // Well above the default 100.000 minimum-saldo floor even after the
    // payment, so this test exercises the happy path, not the floor.
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI);
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 0]);
    $admin = makeUserWithRole('admin');

    $transaksi = app(KantinPembayaranService::class)->bayar($santri, $unit, 15000, $admin);

    expect($transaksi->jenis)->toBe(Transaksi::JENIS_PEMBAYARAN_KANTIN)
        ->and($transaksi->arah)->toBe('debit')
        ->and($santri->saldo->fresh()->saldo)->toBe(285000)
        ->and($unit->fresh()->saldo_unit)->toBe(15000);

    $ledger = $unit->transaksis()->first();
    expect($ledger->transaksi_id)->toBe($transaksi->id)
        ->and($ledger->nominal)->toBe(15000);
});

it('refuses a kantin payment that would drop the santri below the minimum saldo floor', function () {
    $santri = Santri::factory()->create();
    // Default floor (SaldoFloorService) is 100.000 - leaves exactly 90.000
    // after a 15.000 payment, just under it.
    app(WalletService::class)->credit($santri, 105000, Transaksi::JENIS_TOPUP_TUNAI);
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 0]);
    $admin = makeUserWithRole('admin');

    expect(fn () => app(KantinPembayaranService::class)->bayar($santri, $unit, 15000, $admin))
        ->toThrow(App\Exceptions\SaldoDiBawahMinimumException::class);

    expect($santri->saldo->fresh()->saldo)->toBe(105000)
        ->and($unit->fresh()->saldo_unit)->toBe(0);
});

it('refuses a kantin payment when the santri saldo is insufficient, and leaves the kantin saldo untouched', function () {
    $santri = Santri::factory()->create();
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 0]);
    $admin = makeUserWithRole('admin');

    expect(fn () => app(KantinPembayaranService::class)->bayar($santri, $unit, 15000, $admin))
        ->toThrow(App\Exceptions\InsufficientBalanceException::class);

    expect($unit->fresh()->saldo_unit)->toBe(0);
});

it('refuses a kantin payment when the unit usaha is nonaktif', function () {
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 0, 'status' => UnitUsaha::STATUS_NONAKTIF]);
    $admin = makeUserWithRole('admin');

    expect(fn () => app(KantinPembayaranService::class)->bayar($santri, $unit, 15000, $admin))
        ->toThrow(InvalidArgumentException::class);

    expect($santri->saldo->fresh()?->saldo ?? 0)->toBe(100000);
});

it('issues a kwitansi resmi for a successful kantin payment', function () {
    $santri = Santri::factory()->create();
    // Well above the default 100.000 minimum-saldo floor even after the
    // payment, so this test exercises the happy path, not the floor.
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI);
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 0]);
    $admin = makeUserWithRole('admin');

    $transaksi = app(KantinPembayaranService::class)->bayar($santri, $unit, 15000, $admin);

    $kwitansi = Kwitansi::where('transaksi_id', $transaksi->id)->first();
    expect($kwitansi)->not->toBeNull()
        ->and($kwitansi->jenis)->toBe(Kwitansi::JENIS_KANTIN)
        ->and($kwitansi->santri_id)->toBe($santri->id)
        ->and($kwitansi->nominal)->toBe(15000)
        ->and($kwitansi->nomor_kwitansi)->toStartWith('KWT-'.now()->format('Y').'-');
});

it('allows kantin payments under the active daily limit', function () {
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 0]);
    $admin = makeUserWithRole('admin');
    KebijakanKantin::factory()->create(['limit_harian' => 20000]);

    app(KantinPembayaranService::class)->bayar($santri, $unit, 12000, $admin);
    app(KantinPembayaranService::class)->bayar($santri, $unit, 8000, $admin);

    expect($unit->fresh()->saldo_unit)->toBe(20000);
});

it('refuses a kantin payment that would exceed the active daily limit', function () {
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 0]);
    $admin = makeUserWithRole('admin');
    KebijakanKantin::factory()->create(['limit_harian' => 20000]);

    app(KantinPembayaranService::class)->bayar($santri, $unit, 15000, $admin);

    expect(fn () => app(KantinPembayaranService::class)->bayar($santri, $unit, 10000, $admin))
        ->toThrow(LimitKantinHarianException::class);

    // The rejected second payment never touched either ledger.
    expect($unit->fresh()->saldo_unit)->toBe(15000)
        ->and($santri->saldo->fresh()->saldo)->toBe(185000);
});

it('does not restrict kantin payments when no kebijakan kantin is active', function () {
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 0]);
    $admin = makeUserWithRole('admin');
    // Inactive policy - should be ignored entirely.
    KebijakanKantin::factory()->create(['limit_harian' => 5000, 'is_active' => false]);

    app(KantinPembayaranService::class)->bayar($santri, $unit, 50000, $admin);

    expect($unit->fresh()->saldo_unit)->toBe(50000);
});

it('scopes the daily kantin limit per santri, not shared across santri', function () {
    $santriA = Santri::factory()->create();
    $santriB = Santri::factory()->create();
    app(WalletService::class)->credit($santriA, 300000, Transaksi::JENIS_TOPUP_TUNAI);
    app(WalletService::class)->credit($santriB, 300000, Transaksi::JENIS_TOPUP_TUNAI);
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 0]);
    $admin = makeUserWithRole('admin');
    KebijakanKantin::factory()->create(['limit_harian' => 20000]);

    app(KantinPembayaranService::class)->bayar($santriA, $unit, 20000, $admin);
    // Santri B's own limit is untouched by santri A's spending.
    app(KantinPembayaranService::class)->bayar($santriB, $unit, 20000, $admin);

    expect($unit->fresh()->saldo_unit)->toBe(40000);
});
