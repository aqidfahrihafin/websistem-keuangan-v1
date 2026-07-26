<?php

use App\Exceptions\InvalidTransaksiException;
use App\Models\UnitUsaha;
use App\Models\UnitUsahaRekeningPerubahan;
use App\Services\UnitUsahaRekeningService;

it('approves a rekening change and writes it into the unit usaha active fields', function () {
    $unit = UnitUsaha::factory()->create(['bank_nama' => 'BRI', 'bank_no_rekening' => '111', 'bank_atas_nama' => 'Lama']);
    $pengelola = makeUserWithRole('pengelola');
    $admin = makeUserWithRole('admin');
    $service = app(UnitUsahaRekeningService::class);

    $request = $service->ajukan($unit, [
        'bank_nama' => 'BCA',
        'bank_no_rekening' => '222',
        'bank_atas_nama' => 'Baru',
    ], $pengelola);

    expect($request->status)->toBe(UnitUsahaRekeningPerubahan::STATUS_MENUNGGU)
        ->and($unit->fresh()->bank_nama)->toBe('BRI');

    $request = $service->approve($request, $admin);

    expect($request->status)->toBe(UnitUsahaRekeningPerubahan::STATUS_DISETUJUI)
        ->and($unit->fresh()->bank_nama)->toBe('BCA')
        ->and($unit->fresh()->bank_no_rekening)->toBe('222')
        ->and($unit->fresh()->bank_atas_nama)->toBe('Baru');
});

it('rejects a rekening change and leaves the active fields untouched', function () {
    $unit = UnitUsaha::factory()->create(['bank_nama' => 'BRI', 'bank_no_rekening' => '111', 'bank_atas_nama' => 'Lama']);
    $pengelola = makeUserWithRole('pengelola');
    $admin = makeUserWithRole('admin');
    $service = app(UnitUsahaRekeningService::class);

    $request = $service->ajukan($unit, [
        'bank_nama' => 'BCA',
        'bank_no_rekening' => '222',
        'bank_atas_nama' => 'Baru',
    ], $pengelola);

    $request = $service->reject($request, $admin, 'Data tidak sesuai KTP.');

    expect($request->status)->toBe(UnitUsahaRekeningPerubahan::STATUS_DITOLAK)
        ->and($request->catatan_petugas)->toBe('Data tidak sesuai KTP.')
        ->and($unit->fresh()->bank_nama)->toBe('BRI');
});

it('refuses a second pending rekening change while one is already menunggu', function () {
    $unit = UnitUsaha::factory()->create();
    $pengelola = makeUserWithRole('pengelola');
    $service = app(UnitUsahaRekeningService::class);

    $service->ajukan($unit, ['bank_nama' => 'BCA', 'bank_no_rekening' => '222', 'bank_atas_nama' => 'Baru'], $pengelola);

    expect(fn () => $service->ajukan($unit->fresh(), ['bank_nama' => 'BNI', 'bank_no_rekening' => '333', 'bank_atas_nama' => 'Lain'], $pengelola))
        ->toThrow(InvalidTransaksiException::class);
});

it('refuses to approve or reject a request that is not menunggu', function () {
    $unit = UnitUsaha::factory()->create();
    $pengelola = makeUserWithRole('pengelola');
    $admin = makeUserWithRole('admin');
    $service = app(UnitUsahaRekeningService::class);

    $request = $service->ajukan($unit, ['bank_nama' => 'BCA', 'bank_no_rekening' => '222', 'bank_atas_nama' => 'Baru'], $pengelola);
    $service->approve($request, $admin);

    expect(fn () => $service->approve($request->fresh(), $admin))->toThrow(InvalidTransaksiException::class);
    expect(fn () => $service->reject($request->fresh(), $admin))->toThrow(InvalidTransaksiException::class);
});
