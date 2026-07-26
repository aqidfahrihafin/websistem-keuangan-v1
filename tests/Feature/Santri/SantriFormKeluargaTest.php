<?php

use App\Livewire\Admin\Santri\Form as SantriForm;
use App\Models\Keluarga;
use App\Models\Santri;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('reveals the existing family name (read-only) when a santri is saved with a No. KK that already exists', function () {
    $admin = makeUserWithRole('admin');
    Role::findOrCreate('wali', 'web');
    $keluarga = Keluarga::factory()->create(['no_kk' => '1111111111111111', 'nama_kepala_keluarga' => 'Bapak Rahman']);

    Livewire::actingAs($admin)->test(SantriForm::class)
        ->set('nis', '2001000001')
        ->set('nama', 'Santri Baru')
        ->set('status', 'aktif')
        ->set('no_kk', '1111111111111111')
        ->assertSet('keluargaDitemukan.id', $keluarga->id)
        ->assertSet('nama_kepala_keluarga', 'Bapak Rahman')
        ->call('save');

    $santri = Santri::where('nis', '2001000001')->first();
    expect($santri->keluarga_id)->toBe($keluarga->id);
});

it('never overwrites the existing nama_kepala_keluarga when reusing a found keluarga', function () {
    $admin = makeUserWithRole('admin');
    Role::findOrCreate('wali', 'web');
    $keluarga = Keluarga::factory()->create(['no_kk' => '2222222222222222', 'nama_kepala_keluarga' => 'Nama Asli']);

    Livewire::actingAs($admin)->test(SantriForm::class)
        ->set('nis', '2001000002')
        ->set('nama', 'Santri Lain')
        ->set('status', 'aktif')
        ->set('no_kk', '2222222222222222')
        ->call('save');

    expect($keluarga->fresh()->nama_kepala_keluarga)->toBe('Nama Asli');
});

it('creates a new keluarga when the No. KK does not exist yet', function () {
    $admin = makeUserWithRole('admin');
    Role::findOrCreate('wali', 'web');

    Livewire::actingAs($admin)->test(SantriForm::class)
        ->set('nis', '2001000003')
        ->set('nama', 'Santri Ketiga')
        ->set('status', 'aktif')
        ->set('no_kk', '3333333333333333')
        ->assertSet('keluargaDitemukan', null)
        ->set('nama_kepala_keluarga', 'Bapak Baru')
        ->set('alamat_keluarga', 'Jl. Baru No. 1')
        ->call('save')
        ->assertHasNoErrors();

    $keluarga = Keluarga::where('no_kk', '3333333333333333')->first();
    expect($keluarga)->not->toBeNull()
        ->and($keluarga->nama_kepala_keluarga)->toBe('Bapak Baru')
        ->and($keluarga->alamat)->toBe('Jl. Baru No. 1');

    $santri = Santri::where('nis', '2001000003')->first();
    expect($santri->keluarga_id)->toBe($keluarga->id);
});

it('pre-fills the found keluarga when editing a santri that already has one', function () {
    $admin = makeUserWithRole('admin');
    $keluarga = Keluarga::factory()->create();
    $santri = Santri::factory()->create(['keluarga_id' => $keluarga->id]);

    Livewire::actingAs($admin)->test(SantriForm::class, ['santri' => $santri])
        ->assertSet('no_kk', $keluarga->no_kk)
        ->assertSet('keluargaDitemukan.id', $keluarga->id);
});
