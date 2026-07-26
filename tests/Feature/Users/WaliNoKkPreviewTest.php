<?php

use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Models\Keluarga;
use App\Models\Santri;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('previews the matching keluarga when creating a wali user with a known No. KK', function () {
    $admin = makeUserWithRole('admin');
    $keluarga = Keluarga::factory()->create(['no_kk' => '4444444444444444', 'nama_kepala_keluarga' => 'Bapak Yusuf']);
    $santri = Santri::factory()->create(['keluarga_id' => $keluarga->id]);

    Livewire::actingAs($admin)->test(UsersIndex::class)
        ->call('openCreate')
        ->set('role', 'wali')
        ->set('no_kk', '4444444444444444')
        ->assertSet('keluargaDitemukan.id', $keluarga->id)
        ->assertViewHas('title')
        ->assertSee('Bapak Yusuf')
        ->assertSee($santri->nama);
});

it('auto-links a newly created wali to santri that already exist for that No. KK', function () {
    $admin = makeUserWithRole('admin');
    Role::findOrCreate('wali', 'web');
    $keluarga = Keluarga::factory()->create(['no_kk' => '7777777777777777']);
    $santri = Santri::factory()->create(['keluarga_id' => $keluarga->id]);

    Livewire::actingAs($admin)->test(UsersIndex::class)
        ->call('openCreate')
        ->set('role', 'wali')
        ->set('no_kk', '7777777777777777')
        ->set('name', 'Wali Otomatis')
        ->set('password', 'password123')
        ->call('save')
        ->assertHasNoErrors();

    $wali = User::where('name', 'Wali Otomatis')->first();
    expect($wali->anakAsuh()->pluck('santris.id'))->toContain($santri->id);
});

it('shows no keluarga found for an unregistered No. KK, without blocking user creation', function () {
    $admin = makeUserWithRole('admin');
    Role::findOrCreate('wali', 'web');

    Livewire::actingAs($admin)->test(UsersIndex::class)
        ->call('openCreate')
        ->set('role', 'wali')
        ->set('no_kk', '5555555555555555')
        ->assertSet('keluargaDitemukan', null)
        ->set('name', 'Wali Baru')
        ->set('password', 'password123')
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('name', 'Wali Baru')->where('no_kk', '5555555555555555')->exists())->toBeTrue();
});
