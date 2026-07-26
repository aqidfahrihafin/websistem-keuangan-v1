<?php

use App\Livewire\Admin\Kartu\Index as KartuIndex;
use App\Livewire\Admin\KategoriDiskon\Index as KategoriDiskonIndex;
use App\Livewire\Admin\Kebijakan\PenarikanForm as KebijakanPenarikanForm;
use App\Livewire\Admin\Lembaga\Index as LembagaIndex;
use App\Livewire\Admin\Tagihan\JenisIndex;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Models\JenisTagihan;
use App\Models\KartuSantri;
use App\Models\KategoriDiskon;
use App\Models\KebijakanPenarikan;
use App\Models\Lembaga;
use App\Models\Santri;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

it('creates and edits lembaga through the modal on the index page, auto-generating sequential kode', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(LembagaIndex::class)
        ->call('openCreate')
        ->assertSet('showModal', true)
        ->assertSet('kode', 'LBG-001')
        ->set('nama', 'MTs Latee')
        ->set('tipe', 'sekolah_formal')
        ->call('save')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    $lembaga = Lembaga::where('nama', 'MTs Latee')->firstOrFail();
    expect($lembaga->kode)->toBe('LBG-001');

    Livewire::actingAs($admin)->test(LembagaIndex::class)
        ->call('openCreate')
        ->assertSet('kode', 'LBG-002')
        ->set('nama', 'MA Latee')
        ->call('save');

    expect(Lembaga::where('nama', 'MA Latee')->firstOrFail()->kode)->toBe('LBG-002');

    Livewire::actingAs($admin)->test(LembagaIndex::class)
        ->call('openEdit', $lembaga->id)
        ->assertSet('showModal', true)
        ->assertSet('nama', 'MTs Latee')
        ->assertSet('kode', 'LBG-001')
        ->set('nama', 'MTs Latee Updated')
        ->call('save')
        ->assertSet('showModal', false);

    expect($lembaga->fresh()->nama)->toBe('MTs Latee Updated')
        ->and($lembaga->fresh()->kode)->toBe('LBG-001');
});

it('creates kategori diskon through the modal', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(KategoriDiskonIndex::class)
        ->call('openCreate')
        ->set('nama', 'Pengurus Pusat')
        ->set('persentase', 15)
        ->call('save')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    expect(KategoriDiskon::where('nama', 'Pengurus Pusat')->exists())->toBeTrue();
});

it('activates a kartu santri through the modal search-then-activate flow', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create(['nis' => '10023', 'status' => Santri::STATUS_AKTIF]);

    Livewire::actingAs($admin)->test(KartuIndex::class)
        ->call('openAktivasi')
        ->set('nis', '10023')
        ->call('cariSantri')
        ->assertSet('santri.id', $santri->id)
        ->set('nomor_kartu', 'KRT-001')
        ->call('aktivasi')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    expect(KartuSantri::where('nomor_kartu', 'KRT-001')->where('santri_id', $santri->id)->exists())->toBeTrue();
});

it('creates and edits a user through the modal', function () {
    $admin = makeUserWithRole('admin');
    Role::findOrCreate('bendahara', 'web');

    Livewire::actingAs($admin)->test(UsersIndex::class)
        ->call('openCreate')
        ->set('name', 'Budi Bendahara')
        ->set('email', 'budi@example.test')
        ->set('password', 'password123')
        ->set('role', 'bendahara')
        ->call('save')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    $user = User::where('email', 'budi@example.test')->firstOrFail();
    expect($user->hasRole('bendahara'))->toBeTrue();

    Livewire::actingAs($admin)->test(UsersIndex::class)
        ->call('openEdit', $user->id)
        ->assertSet('name', 'Budi Bendahara')
        ->set('name', 'Budi Bendahara Updated')
        ->call('save')
        ->assertSet('showModal', false);

    expect($user->fresh()->name)->toBe('Budi Bendahara Updated');
});

it('creates and edits jenis tagihan through the modal, auto-generating sequential kode', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(JenisIndex::class)
        ->call('openCreate')
        ->assertSet('kode', 'JT-001')
        ->set('nama', 'SPP Bulanan')
        ->set('nominal_default', 150000)
        ->call('save')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    $jenis = JenisTagihan::where('nama', 'SPP Bulanan')->firstOrFail();
    expect($jenis->kode)->toBe('JT-001');

    Livewire::actingAs($admin)->test(JenisIndex::class)
        ->call('openEdit', $jenis->id)
        ->assertSet('nama', 'SPP Bulanan')
        ->assertSet('kode', 'JT-001')
        ->set('nominal_default', 175000)
        ->call('save')
        ->assertSet('showModal', false);

    expect($jenis->fresh()->nominal_default)->toBe(175000)
        ->and($jenis->fresh()->kode)->toBe('JT-001');
});

it('lets an admin toggle bisa_dicicil on a jenis tagihan through the modal', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(JenisIndex::class)
        ->call('openCreate')
        ->assertSet('bisa_dicicil', false)
        ->set('nama', 'Uang Pangkal')
        ->set('nominal_default', 500000)
        ->set('bisa_dicicil', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(JenisTagihan::where('nama', 'Uang Pangkal')->firstOrFail()->bisa_dicicil)->toBeTrue();
});

it('creates a kebijakan penarikan through the modal and toggles its active status', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(KebijakanPenarikanForm::class)
        ->call('openCreate')
        ->assertSet('showModal', true)
        ->set('nama', 'Kebijakan Jam Kerja')
        ->set('jam_mulai', '08:00')
        ->set('jam_selesai', '15:00')
        ->set('limit_harian', 75000)
        ->call('simpan')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    $kebijakan = KebijakanPenarikan::where('nama', 'Kebijakan Jam Kerja')->firstOrFail();
    expect($kebijakan->is_active)->toBeTrue();

    Livewire::actingAs($admin)->test(KebijakanPenarikanForm::class)
        ->call('toggleActive', $kebijakan->id);

    expect($kebijakan->fresh()->is_active)->toBeFalse();
});
