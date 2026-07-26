<?php

use App\Livewire\Admin\Santri\Form as SantriForm;
use App\Models\Keluarga;
use App\Models\Santri;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('wali', 'web');
});

it('creates a default wali account (same person as kepala keluarga) when adding a santri with a brand new No. KK', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(SantriForm::class)
        ->set('nis', '4001000001')
        ->set('nama', 'Santri Satu')
        ->set('status', 'aktif')
        ->set('no_kk', '5555444433332222')
        ->set('nama_kepala_keluarga', 'Bapak Slamet')
        ->call('save')
        ->assertHasNoErrors();

    $wali = User::where('no_kk', '5555444433332222')->first();
    expect($wali)->not->toBeNull()
        ->and($wali->name)->toBe('Bapak Slamet')
        ->and($wali->must_change_password)->toBeTrue()
        ->and(Hash::check('5555444433332222', $wali->password))->toBeTrue()
        ->and($wali->hasRole('wali'))->toBeTrue();

    $santri = Santri::where('nis', '4001000001')->first();
    expect($wali->anakAsuh()->pluck('santris.id'))->toContain($santri->id);
});

it('creates a wali account with different data when "wali sama dengan kepala keluarga" is unchecked', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(SantriForm::class)
        ->set('nis', '4001000002')
        ->set('nama', 'Santri Dua')
        ->set('status', 'aktif')
        ->set('no_kk', '6666555544443333')
        ->set('nama_kepala_keluarga', 'Bapak Slamet')
        ->set('waliSamaDenganKepalaKeluarga', false)
        ->set('wali_nama', 'Ibu Marni')
        ->set('wali_email', 'ibu.marni@example.test')
        ->call('save')
        ->assertHasNoErrors();

    $wali = User::where('email', 'ibu.marni@example.test')->first();
    expect($wali)->not->toBeNull()
        ->and($wali->name)->toBe('Ibu Marni')
        ->and($wali->no_kk)->toBe('6666555544443333')
        ->and(Hash::check('6666555544443333', $wali->password))->toBeTrue();
});

it('requires wali_nama when wali sama dengan kepala keluarga is unchecked', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(SantriForm::class)
        ->set('nis', '4001000003')
        ->set('nama', 'Santri Tiga')
        ->set('status', 'aktif')
        ->set('no_kk', '1231231231231234')
        ->set('nama_kepala_keluarga', 'Bapak X')
        ->set('waliSamaDenganKepalaKeluarga', false)
        ->call('save')
        ->assertHasErrors(['wali_nama']);

    expect(User::where('no_kk', '1231231231231234')->exists())->toBeFalse();
});

it('does not create a wali account when the "buatkan akun wali" checkbox is unchecked', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(SantriForm::class)
        ->set('nis', '4001000004')
        ->set('nama', 'Santri Empat')
        ->set('status', 'aktif')
        ->set('no_kk', '9998887776665554')
        ->set('nama_kepala_keluarga', 'Bapak Y')
        ->set('buatAkunWali', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('no_kk', '9998887776665554')->exists())->toBeFalse()
        ->and(Keluarga::where('no_kk', '9998887776665554')->exists())->toBeTrue();
});

it('does not offer to create a wali account when one already exists for the No. KK', function () {
    $admin = makeUserWithRole('admin');
    $keluarga = Keluarga::factory()->create(['no_kk' => '1112223334445556']);
    User::factory()->create(['no_kk' => '1112223334445556'])->assignRole('wali');

    Livewire::actingAs($admin)->test(SantriForm::class)
        ->set('nis', '4001000005')
        ->set('nama', 'Santri Lima')
        ->set('status', 'aktif')
        ->set('no_kk', '1112223334445556')
        ->assertSet('adaWaliUntukKeluarga', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(User::where('no_kk', '1112223334445556')->count())->toBe(1);
});
