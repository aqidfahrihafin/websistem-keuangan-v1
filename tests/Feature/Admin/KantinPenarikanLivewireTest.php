<?php

use App\Livewire\Admin\Kantin\Penarikan as AdminKantinPenarikan;
use App\Models\UnitUsaha;
use App\Models\UnitUsahaPenarikan;
use App\Services\UnitUsahaPenarikanService;
use Livewire\Livewire;

it('requires a transfer reference before an approved request can be cairkan-ed', function () {
    $admin = makeUserWithRole('admin');
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 100000]);
    $request = app(UnitUsahaPenarikanService::class)->createRequest($unit, 40000, $admin);
    $request = app(UnitUsahaPenarikanService::class)->approve($request, $admin);

    $this->actingAs($admin);

    Livewire::test(AdminKantinPenarikan::class)
        ->call('openCairkan', $request->id)
        ->call('cairkan')
        ->assertHasErrors('referensi_transfer');

    expect($request->fresh()->status)->toBe(UnitUsahaPenarikan::STATUS_DISETUJUI);

    Livewire::test(AdminKantinPenarikan::class)
        ->call('openCairkan', $request->id)
        ->set('referensi_transfer', 'TRX-BCA-999')
        ->call('cairkan')
        ->assertHasNoErrors();

    expect($request->fresh()->status)->toBe(UnitUsahaPenarikan::STATUS_SELESAI)
        ->and($request->fresh()->referensi_transfer)->toBe('TRX-BCA-999');
});

it('does not let admin create a withdrawal request - only the owning pengelola can', function () {
    $admin = makeUserWithRole('admin');
    UnitUsaha::factory()->create(['saldo_unit' => 100000]);

    $this->actingAs($admin)->get(route('admin.kantin.penarikan.index'))
        ->assertOk()
        ->assertDontSee('Ajukan Penarikan');

    expect(method_exists(AdminKantinPenarikan::class, 'ajukan'))->toBeFalse()
        ->and(UnitUsahaPenarikan::count())->toBe(0);
});
