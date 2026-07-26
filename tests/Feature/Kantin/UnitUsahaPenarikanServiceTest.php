<?php

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidTransaksiException;
use App\Models\UnitUsaha;
use App\Models\UnitUsahaPenarikan;
use App\Services\UnitUsahaPenarikanService;

it('walks a withdrawal request through menunggu -> disetujui -> selesai, debits saldo_unit only on fulfill, and stores the transfer reference', function () {
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 100000]);
    $admin = makeUserWithRole('admin');
    $service = app(UnitUsahaPenarikanService::class);

    $request = $service->createRequest($unit, 40000, $admin);
    expect($request->status)->toBe(UnitUsahaPenarikan::STATUS_MENUNGGU)
        ->and($unit->fresh()->saldo_unit)->toBe(100000);

    $request = $service->approve($request, $admin);
    expect($request->status)->toBe(UnitUsahaPenarikan::STATUS_DISETUJUI)
        ->and($unit->fresh()->saldo_unit)->toBe(100000);

    $request = $service->fulfill($request, $admin, 'TRX-BCA-001');
    expect($request->status)->toBe(UnitUsahaPenarikan::STATUS_SELESAI)
        ->and($request->referensi_transfer)->toBe('TRX-BCA-001')
        ->and($request->unit_usaha_transaksi_id)->not->toBeNull()
        ->and($unit->fresh()->saldo_unit)->toBe(60000);
});

it('rejects a withdrawal request and never touches saldo_unit', function () {
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 50000]);
    $admin = makeUserWithRole('admin');
    $service = app(UnitUsahaPenarikanService::class);

    $request = $service->createRequest($unit, 20000, $admin);
    $request = $service->reject($request, $admin, 'Belum waktunya.');

    expect($request->status)->toBe(UnitUsahaPenarikan::STATUS_DITOLAK)
        ->and($request->catatan_petugas)->toBe('Belum waktunya.')
        ->and($unit->fresh()->saldo_unit)->toBe(50000);
});

it('refuses to approve a request that is not menunggu', function () {
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 50000]);
    $admin = makeUserWithRole('admin');
    $service = app(UnitUsahaPenarikanService::class);

    $request = $service->createRequest($unit, 20000, $admin);
    $service->approve($request, $admin);

    expect(fn () => $service->approve($request->fresh(), $admin))->toThrow(InvalidTransaksiException::class);
});

it('refuses to fulfill a request that has not been approved yet', function () {
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 50000]);
    $admin = makeUserWithRole('admin');
    $service = app(UnitUsahaPenarikanService::class);

    $request = $service->createRequest($unit, 20000, $admin);

    expect(fn () => $service->fulfill($request, $admin, 'TRX-1'))->toThrow(InvalidTransaksiException::class);
    expect($unit->fresh()->saldo_unit)->toBe(50000);
});

it('refuses to fulfill without a transfer reference as proof of disbursement', function () {
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 50000]);
    $admin = makeUserWithRole('admin');
    $service = app(UnitUsahaPenarikanService::class);

    $request = $service->createRequest($unit, 20000, $admin);
    $request = $service->approve($request, $admin);

    expect(fn () => $service->fulfill($request, $admin, ''))->toThrow(InvalidArgumentException::class);
    expect($unit->fresh()->saldo_unit)->toBe(50000);
});

it('refuses to create a withdrawal request larger than the unit usaha\'s current saldo_unit', function () {
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 0]);
    $admin = makeUserWithRole('admin');
    $service = app(UnitUsahaPenarikanService::class);

    expect(fn () => $service->createRequest($unit, 10000, $admin))->toThrow(InsufficientBalanceException::class);
});

it('refuses to fulfill a withdrawal that no longer fits the saldo_unit at fulfill time', function () {
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 50000]);
    $admin = makeUserWithRole('admin');
    $service = app(UnitUsahaPenarikanService::class);

    $request = $service->createRequest($unit, 50000, $admin);
    $request = $service->approve($request, $admin);

    // Balance can still drop between approval and fulfillment (e.g. another
    // request fulfilled in the meantime) - fulfill()'s own check via
    // UnitUsahaWalletService::debit() must still catch this even though
    // createRequest() only validated the balance at request time.
    $unit->update(['saldo_unit' => 10000]);

    expect(fn () => $service->fulfill($request->fresh(), $admin, 'TRX-1'))->toThrow(InsufficientBalanceException::class);
});

it('requires the handover code for cash disbursement and lets the owning manager confirm receipt', function () {
    $pengelola = makeUserWithRole('pengelola');
    $admin = makeUserWithRole('admin');
    $unit = UnitUsaha::factory()->create([
        'saldo_unit' => 100000,
        'pengelola_user_id' => $pengelola->id,
    ]);
    $service = app(UnitUsahaPenarikanService::class);

    $request = $service->createRequest(
        $unit,
        25000,
        $pengelola,
        UnitUsahaPenarikan::METODE_TUNAI,
    );
    $request = $service->approve($request, $admin);

    expect($request->kode_serah_terima)->toHaveLength(6);
    expect(fn () => $service->fulfill($request, $admin, null, '000000'))
        ->toThrow(InvalidArgumentException::class);

    $request = $service->fulfill($request->fresh(), $admin, null, $request->kode_serah_terima);
    expect($request->status)->toBe(UnitUsahaPenarikan::STATUS_SELESAI)
        ->and($request->diserahkan_at)->not->toBeNull()
        ->and($unit->fresh()->saldo_unit)->toBe(75000);

    $request = $service->confirmReceived($request, $pengelola);
    expect($request->dikonfirmasi_oleh)->toBe($pengelola->id)
        ->and($request->dikonfirmasi_at)->not->toBeNull();
});

it('prevents another manager from confirming receipt', function () {
    $pengelola = makeUserWithRole('pengelola');
    $pengelolaLain = makeUserWithRole('pengelola');
    $admin = makeUserWithRole('admin');
    $unit = UnitUsaha::factory()->create([
        'saldo_unit' => 50000,
        'pengelola_user_id' => $pengelola->id,
    ]);
    $service = app(UnitUsahaPenarikanService::class);

    $request = $service->createRequest($unit, 10000, $pengelola);
    $request = $service->approve($request, $admin);
    $request = $service->fulfill($request, $admin, 'TRX-002');

    expect(fn () => $service->confirmReceived($request, $pengelolaLain))
        ->toThrow(InvalidTransaksiException::class);
});
