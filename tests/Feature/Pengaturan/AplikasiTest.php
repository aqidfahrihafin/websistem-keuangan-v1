<?php

use App\Livewire\Admin\Pengaturan\Aplikasi;
use App\Services\AppSettingsService;
use Livewire\Livewire;

it('falls back to sensible defaults when no setting has been saved yet', function () {
    $service = app(AppSettingsService::class);

    expect($service->namaAplikasi())->toBe('Sistem Keuangan Santri')
        ->and($service->namaPondok())->toBe('Pondok Pesantren Latee (Annuqayah)')
        ->and($service->alamat())->toBeNull()
        ->and($service->telepon())->toBeNull()
        ->and($service->email())->toBeNull();
});

it('derives short initials from the app and pondok names for tight spaces like the sidebar header', function () {
    $service = app(AppSettingsService::class);

    expect($service->namaAplikasiInisial())->toBe('SKS')
        ->and($service->namaPondokInisial())->toBe('PPLA');

    $service->save('Kas Digital', 'Pondok Pesantren Contoh', null, null, null);

    expect($service->namaAplikasiInisial())->toBe('KD')
        ->and($service->namaPondokInisial())->toBe('PPC');
});

it('persists app settings and reads them back through the service', function () {
    $service = app(AppSettingsService::class);

    $service->save('Kas Santri Digital', 'Pondok Pesantren Contoh', 'Jl. Contoh No. 1', '0812345678', 'kontak@pesantren.test');

    expect($service->namaAplikasi())->toBe('Kas Santri Digital')
        ->and($service->namaPondok())->toBe('Pondok Pesantren Contoh')
        ->and($service->alamat())->toBe('Jl. Contoh No. 1')
        ->and($service->telepon())->toBe('0812345678')
        ->and($service->email())->toBe('kontak@pesantren.test');
});

it('lets an admin update app settings through the form, but forbids other roles', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Aplikasi::class)
        ->assertSet('nama_aplikasi', 'Sistem Keuangan Santri')
        ->set('nama_aplikasi', 'Kas Santri Digital')
        ->set('nama_pondok', 'Pondok Pesantren Contoh')
        ->set('alamat', 'Jl. Contoh No. 1')
        ->set('telepon', '0812345678')
        ->set('email', 'kontak@pesantren.test')
        ->call('simpan')
        ->assertHasNoErrors();

    expect(app(AppSettingsService::class)->namaAplikasi())->toBe('Kas Santri Digital');

    $wali = makeUserWithRole('wali');
    $this->actingAs($wali)->get(route('admin.pengaturan.aplikasi'))->assertForbidden();
});

it('requires nama_aplikasi, nama_pondok, and a valid email', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Aplikasi::class)
        ->set('nama_aplikasi', '')
        ->set('nama_pondok', '')
        ->set('email', 'bukan-email')
        ->call('simpan')
        ->assertHasErrors(['nama_aplikasi' => 'required', 'nama_pondok' => 'required', 'email' => 'email']);
});

it('sets a page-level success message immediately after saving, not via a session flash that needs a page reload', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Aplikasi::class)
        ->assertSet('statusMessage', null)
        ->set('nama_aplikasi', 'Kas Santri Digital')
        ->set('nama_pondok', 'Pondok Pesantren Contoh')
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertSet('statusMessage', 'Pengaturan aplikasi berhasil disimpan.')
        ->assertSee('Pengaturan aplikasi berhasil disimpan.');
});

it('sets a page-level error message when required fields are missing', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Aplikasi::class)
        ->set('nama_aplikasi', '')
        ->call('simpan')
        ->assertHasErrors(['nama_aplikasi'])
        ->assertSet('statusMessage', null)
        ->assertSet('errorMessage', 'Gagal menyimpan. Periksa kembali isian yang ditandai di bawah.');
});
