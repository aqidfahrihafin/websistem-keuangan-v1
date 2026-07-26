<?php

use App\Models\UnitUsaha;
use App\Models\UnitUsahaTransaksi;
use App\Services\UnitUsahaWalletService;

it('credits a unit usaha and writes an immutable ledger row', function () {
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 0]);

    $ledger = app(UnitUsahaWalletService::class)->credit($unit, 50000, UnitUsahaTransaksi::JENIS_PEMBAYARAN_MASUK);

    expect($unit->fresh()->saldo_unit)->toBe(50000)
        ->and($ledger->arah)->toBe('kredit')
        ->and($ledger->saldo_sebelum)->toBe(0)
        ->and($ledger->saldo_sesudah)->toBe(50000);
});

it('debits a unit usaha with enough saldo_unit', function () {
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 100000]);

    $ledger = app(UnitUsahaWalletService::class)->debit($unit, 40000, UnitUsahaTransaksi::JENIS_PENARIKAN_KELUAR);

    expect($unit->fresh()->saldo_unit)->toBe(60000)
        ->and($ledger->arah)->toBe('debit');
});

it('refuses to debit a unit usaha with insufficient saldo_unit', function () {
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 10000]);

    expect(fn () => app(UnitUsahaWalletService::class)->debit($unit, 50000, UnitUsahaTransaksi::JENIS_PENARIKAN_KELUAR))
        ->toThrow(App\Exceptions\InsufficientBalanceException::class);

    expect($unit->fresh()->saldo_unit)->toBe(10000);
});

it('rejects a non-positive nominal for credit or debit', function () {
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 10000]);
    $service = app(UnitUsahaWalletService::class);

    expect(fn () => $service->credit($unit, 0, UnitUsahaTransaksi::JENIS_PEMBAYARAN_MASUK))->toThrow(InvalidArgumentException::class);
    expect(fn () => $service->debit($unit, -5, UnitUsahaTransaksi::JENIS_PENARIKAN_KELUAR))->toThrow(InvalidArgumentException::class);
});

it('cannot update or delete a unit usaha ledger row once created', function () {
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 0]);
    $ledger = app(UnitUsahaWalletService::class)->credit($unit, 20000, UnitUsahaTransaksi::JENIS_PEMBAYARAN_MASUK);

    expect(fn () => $ledger->update(['nominal' => 99999]))->toThrow(App\Exceptions\ImmutableLedgerException::class);
    expect(fn () => $ledger->delete())->toThrow(App\Exceptions\ImmutableLedgerException::class);
});
