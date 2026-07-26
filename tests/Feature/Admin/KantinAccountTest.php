<?php

use App\Livewire\Admin\Kantin\Index as AdminKantinIndex;
use App\Models\UnitUsaha;
use App\Models\UnitUsahaRekeningPerubahan;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('lets admin create a pengelola account for a unit that has none yet', function () {
    Role::findOrCreate('pengelola', 'web');
    $admin = makeUserWithRole('admin');
    $unit = UnitUsaha::factory()->create();

    $this->actingAs($admin);

    $component = Livewire::test(AdminKantinIndex::class)
        ->call('openBuatAkunPengelola', $unit->id)
        ->set('pengelola_nama', 'Budi Santoso')
        ->set('pengelola_email', 'budi@kantin.test')
        ->call('buatAkunPengelola');

    expect($component->get('generatedPassword'))->not->toBeEmpty()
        ->and($unit->fresh()->pengelola)->not->toBeNull()
        ->and($unit->fresh()->pengelola->email)->toBe('budi@kantin.test')
        ->and($unit->fresh()->pengelola->hasRole('pengelola'))->toBeTrue();
});

it('blocks creating a second pengelola account once a unit already has one', function () {
    Role::findOrCreate('pengelola', 'web');
    $admin = makeUserWithRole('admin');
    $unit = UnitUsaha::factory()->create();

    $this->actingAs($admin);

    Livewire::test(AdminKantinIndex::class)
        ->call('openBuatAkunPengelola', $unit->id)
        ->set('pengelola_nama', 'Budi Santoso')
        ->set('pengelola_email', 'budi@kantin.test')
        ->call('buatAkunPengelola');

    Livewire::test(AdminKantinIndex::class)
        ->call('openBuatAkunPengelola', $unit->fresh()->id)
        ->set('pengelola_nama', 'Orang Lain')
        ->set('pengelola_email', 'lain@kantin.test')
        ->call('buatAkunPengelola')
        ->assertHasErrors('pengelola_nama');

    expect(\App\Models\User::where('email', 'lain@kantin.test')->exists())->toBeFalse();
});

it('lets admin edit bank fields directly without creating a rekening-perubahan request', function () {
    $admin = makeUserWithRole('admin');
    $unit = UnitUsaha::factory()->create();

    $this->actingAs($admin);

    Livewire::test(AdminKantinIndex::class)
        ->call('openEdit', $unit->id)
        ->set('bank_nama', 'BCA')
        ->set('bank_no_rekening', '12345')
        ->set('bank_atas_nama', 'Pemilik Kantin')
        ->call('save');

    expect($unit->fresh()->bank_nama)->toBe('BCA')
        ->and(UnitUsahaRekeningPerubahan::count())->toBe(0);
});
