<?php

use App\Livewire\Admin\Keluarga\Index as KeluargaIndex;
use App\Models\Keluarga;
use App\Models\Santri;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('shows an admin the existing keluarga instead of letting them blindly duplicate a No. KK', function () {
    $admin = makeUserWithRole('admin');
    $keluarga = Keluarga::factory()->create(['no_kk' => '1234567890123456', 'nama_kepala_keluarga' => 'Bapak Sudirman']);

    Livewire::actingAs($admin)->test(KeluargaIndex::class)
        ->call('openCreate')
        ->set('no_kk', '1234567890123456')
        ->call('cekNoKk')
        ->assertSet('keluargaDitemukan.id', $keluarga->id);
});

it('reveals the create fields only after a No. KK that does not exist yet is checked', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(KeluargaIndex::class)
        ->call('openCreate')
        ->set('no_kk', '9999999999999999')
        ->call('cekNoKk')
        ->assertSet('keluargaDitemukan', null)
        ->set('nama_kepala_keluarga', 'Ibu Aminah')
        ->set('alamat', 'Jl. Contoh No. 1')
        ->call('save')
        ->assertHasNoErrors();

    expect(Keluarga::where('no_kk', '9999999999999999')->where('nama_kepala_keluarga', 'Ibu Aminah')->exists())->toBeTrue();
});

it('lets an admin edit an existing keluarga name and alamat but not its No. KK', function () {
    $admin = makeUserWithRole('admin');
    $keluarga = Keluarga::factory()->create(['nama_kepala_keluarga' => 'Nama Lama']);

    Livewire::actingAs($admin)->test(KeluargaIndex::class)
        ->call('openEdit', $keluarga->id)
        ->assertSet('no_kk', $keluarga->no_kk)
        ->set('nama_kepala_keluarga', 'Nama Baru')
        ->call('save')
        ->assertHasNoErrors();

    expect($keluarga->fresh()->nama_kepala_keluarga)->toBe('Nama Baru')
        ->and($keluarga->fresh()->no_kk)->toBe($keluarga->no_kk);
});

it('lets an admin edit the kepala keluarga biodata (NIK, tempat/tanggal lahir) added after creation', function () {
    $admin = makeUserWithRole('admin');
    $keluarga = Keluarga::factory()->create(['nik_kepala_keluarga' => null, 'tempat_lahir_kepala_keluarga' => null, 'tanggal_lahir_kepala_keluarga' => null]);

    Livewire::actingAs($admin)->test(KeluargaIndex::class)
        ->call('openEdit', $keluarga->id)
        ->assertSet('nik_kepala_keluarga', null)
        ->set('nik_kepala_keluarga', '1234567812345678')
        ->set('tempat_lahir_kepala_keluarga', 'Sumenep')
        ->set('tanggal_lahir_kepala_keluarga', '1980-01-15')
        ->call('save')
        ->assertHasNoErrors();

    expect($keluarga->fresh()->nik_kepala_keluarga)->toBe('1234567812345678')
        ->and($keluarga->fresh()->tempat_lahir_kepala_keluarga)->toBe('Sumenep')
        ->and($keluarga->fresh()->tanggal_lahir_kepala_keluarga->toDateString())->toBe('1980-01-15');
});

it('rejects a kepala keluarga NIK already used by another keluarga', function () {
    $admin = makeUserWithRole('admin');
    Keluarga::factory()->create(['nik_kepala_keluarga' => '9999999999999999']);
    $keluarga = Keluarga::factory()->create();

    Livewire::actingAs($admin)->test(KeluargaIndex::class)
        ->call('openEdit', $keluarga->id)
        ->set('nik_kepala_keluarga', '9999999999999999')
        ->call('save')
        ->assertHasErrors(['nik_kepala_keluarga']);
});

it('shows the santri and wali linked to a keluarga when its detail row is expanded', function () {
    $admin = makeUserWithRole('admin');
    $keluarga = Keluarga::factory()->create();
    $santri = Santri::factory()->create(['keluarga_id' => $keluarga->id]);
    makeUserWithRole('wali', ['no_kk' => $keluarga->no_kk]);

    Livewire::actingAs($admin)->test(KeluargaIndex::class)
        ->call('toggleDetail', $keluarga->id)
        ->assertViewHas('keluargas', function ($keluargas) use ($keluarga) {
            $found = $keluargas->firstWhere('id', $keluarga->id);

            return $found && $found->santris_count === 1 && $found->wali_users_count === 1;
        });
});

it('creates a wali account directly from a keluarga row and auto-links it to that keluarga\'s santri', function () {
    $admin = makeUserWithRole('admin');
    Role::findOrCreate('wali', 'web');
    $keluarga = Keluarga::factory()->create(['no_kk' => '6666666666666666', 'nama_kepala_keluarga' => 'Bapak Aqid']);
    $santri = Santri::factory()->create(['keluarga_id' => $keluarga->id]);

    Livewire::actingAs($admin)->test(KeluargaIndex::class)
        ->call('openBuatWali', $keluarga->id)
        ->assertSet('keluargaUntukWali.id', $keluarga->id)
        ->set('wali_name', 'Ibu Aqid')
        ->set('wali_email', 'ibu.aqid@example.test')
        ->set('wali_password', 'password123')
        ->call('simpanWali')
        ->assertHasNoErrors();

    $wali = User::where('email', 'ibu.aqid@example.test')->first();
    expect($wali)->not->toBeNull()
        ->and($wali->no_kk)->toBe('6666666666666666')
        ->and($wali->hasRole('wali'))->toBeTrue()
        ->and($wali->anakAsuh()->pluck('santris.id'))->toContain($santri->id);
});

it('requires a name and password to create a wali account from the keluarga page', function () {
    $admin = makeUserWithRole('admin');
    Role::findOrCreate('wali', 'web');
    $keluarga = Keluarga::factory()->create();

    Livewire::actingAs($admin)->test(KeluargaIndex::class)
        ->call('openBuatWali', $keluarga->id)
        ->set('wali_name', '')
        ->set('wali_password', '')
        ->call('simpanWali')
        ->assertHasErrors(['wali_name', 'wali_password']);
});

it('forbids non-admin roles from reaching the keluarga admin page', function () {
    $wali = makeUserWithRole('wali');

    $this->actingAs($wali)->get(route('admin.keluarga.index'))->assertForbidden();
});
