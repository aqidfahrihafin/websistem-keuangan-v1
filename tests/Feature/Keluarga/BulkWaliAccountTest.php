<?php

use App\Livewire\Admin\Keluarga\Index as KeluargaIndex;
use App\Models\Keluarga;
use App\Models\Santri;
use App\Models\User;
use App\Services\WaliAccountService;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('wali', 'web');
});

it('creates a default wali account for every keluarga that has none yet', function () {
    $keluargaA = Keluarga::factory()->create(['no_kk' => '1111000011110000', 'nama_kepala_keluarga' => 'Bapak A']);
    Santri::factory()->create(['keluarga_id' => $keluargaA->id]);

    $keluargaB = Keluarga::factory()->create(['no_kk' => '2222000022220000', 'nama_kepala_keluarga' => 'Bapak B']);
    Santri::factory()->count(2)->create(['keluarga_id' => $keluargaB->id]);

    // Already has a wali - must be skipped by the bulk pass.
    $keluargaC = Keluarga::factory()->create(['no_kk' => '3333000033330000']);
    User::factory()->create(['no_kk' => '3333000033330000'])->assignRole('wali');

    $hasil = app(WaliAccountService::class)->buatAkunMassalUntukSemua();

    expect($hasil)->toHaveCount(2)
        ->and(User::where('no_kk', '1111000011110000')->exists())->toBeTrue()
        ->and(User::where('no_kk', '2222000022220000')->exists())->toBeTrue()
        ->and(User::where('no_kk', '3333000033330000')->count())->toBe(1);

    $waliA = User::where('no_kk', '1111000011110000')->first();
    expect($waliA->name)->toBe('Bapak A')
        ->and($waliA->must_change_password)->toBeTrue()
        ->and(Hash::check('1111000011110000', $waliA->password))->toBeTrue();
});

it('redirects to the pdf download route after creating the accounts, which then serves the credential list', function () {
    $admin = makeUserWithRole('admin');
    Keluarga::factory()->create(['no_kk' => '4444000044440000', 'nama_kepala_keluarga' => 'Bapak D']);

    Livewire::actingAs($admin)->test(KeluargaIndex::class)
        ->call('bulkBuatAkunWali')
        ->assertRedirect(route('admin.keluarga.unduh-akun-wali-baru'));

    expect(User::where('no_kk', '4444000044440000')->exists())->toBeTrue();

    $this->actingAs($admin)->get(route('admin.keluarga.unduh-akun-wali-baru'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('404s the download route once the pending list has already been consumed', function () {
    $admin = makeUserWithRole('admin');

    $this->actingAs($admin)->get(route('admin.keluarga.unduh-akun-wali-baru'))->assertNotFound();
});

it('does not redirect or create anything when every keluarga already has a wali', function () {
    $admin = makeUserWithRole('admin');
    $keluarga = Keluarga::factory()->create(['no_kk' => '5555000055550000']);
    User::factory()->create(['no_kk' => '5555000055550000'])->assignRole('wali');

    Livewire::actingAs($admin)->test(KeluargaIndex::class)
        ->call('bulkBuatAkunWali')
        ->assertNoRedirect();

    expect(User::where('no_kk', $keluarga->no_kk)->count())->toBe(1);
});
