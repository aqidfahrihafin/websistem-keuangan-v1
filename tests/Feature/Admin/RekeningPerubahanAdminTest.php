<?php

use App\Livewire\Admin\Kantin\RekeningPerubahan as AdminRekeningPerubahan;
use App\Models\UnitUsaha;
use App\Models\UnitUsahaRekeningPerubahan;
use App\Services\UnitUsahaRekeningService;
use Livewire\Livewire;

it('lets admin approve a pending rekening-perubahan request, updating the active bank fields', function () {
    $admin = makeUserWithRole('admin');
    $pengelola = makeUserWithRole('pengelola');
    $unit = UnitUsaha::factory()->create();

    $request = app(UnitUsahaRekeningService::class)->ajukan($unit, [
        'bank_nama' => 'BCA',
        'bank_no_rekening' => '222',
        'bank_atas_nama' => 'Baru',
    ], $pengelola);

    $this->actingAs($admin);

    Livewire::test(AdminRekeningPerubahan::class)
        ->call('approve', $request->id);

    expect($request->fresh()->status)->toBe(UnitUsahaRekeningPerubahan::STATUS_DISETUJUI)
        ->and($unit->fresh()->bank_nama)->toBe('BCA');
});

it('lets admin reject a pending rekening-perubahan request, leaving the active bank fields untouched', function () {
    $admin = makeUserWithRole('admin');
    $pengelola = makeUserWithRole('pengelola');
    $unit = UnitUsaha::factory()->create(['bank_nama' => 'BRI']);

    $request = app(UnitUsahaRekeningService::class)->ajukan($unit, [
        'bank_nama' => 'BCA',
        'bank_no_rekening' => '222',
        'bank_atas_nama' => 'Baru',
    ], $pengelola);

    $this->actingAs($admin);

    Livewire::test(AdminRekeningPerubahan::class)
        ->call('reject', $request->id);

    expect($request->fresh()->status)->toBe(UnitUsahaRekeningPerubahan::STATUS_DITOLAK)
        ->and($unit->fresh()->bank_nama)->toBe('BRI');
});
