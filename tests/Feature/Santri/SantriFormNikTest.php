<?php

use App\Livewire\Admin\Santri\Form as SantriForm;
use App\Models\Santri;
use Livewire\Livewire;

it('saves a santri with a valid NIK', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(SantriForm::class)
        ->set('nis', '3001000001')
        ->set('nik', '3529120510100099')
        ->set('nama', 'Santri NIK')
        ->set('status', 'aktif')
        ->call('save')
        ->assertHasNoErrors();

    expect(Santri::where('nis', '3001000001')->first()?->nik)->toBe('3529120510100099');
});

it('rejects a NIK that is not exactly 16 digits', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(SantriForm::class)
        ->set('nis', '3001000002')
        ->set('nik', '12345')
        ->set('nama', 'Santri NIK Salah')
        ->set('status', 'aktif')
        ->call('save')
        ->assertHasErrors(['nik']);
});

it('rejects a NIK already used by another santri', function () {
    Santri::factory()->create(['nik' => '1111222233334444']);
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(SantriForm::class)
        ->set('nis', '3001000003')
        ->set('nik', '1111222233334444')
        ->set('nama', 'Santri NIK Dobel')
        ->set('status', 'aktif')
        ->call('save')
        ->assertHasErrors(['nik']);
});

it('allows saving without a NIK since it is optional', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(SantriForm::class)
        ->set('nis', '3001000004')
        ->set('nama', 'Santri Tanpa NIK')
        ->set('status', 'aktif')
        ->call('save')
        ->assertHasNoErrors();

    expect(Santri::where('nis', '3001000004')->first()?->nik)->toBeNull();
});
