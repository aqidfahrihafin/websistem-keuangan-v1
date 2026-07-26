<?php

use App\Livewire\Admin\Pengaturan\Midtrans;
use App\Services\MidtransFeeService;
use App\Services\SaldoFloorService;
use App\Services\TopupWaliService;
use Livewire\Livewire;

it('falls back to a default minimal saldo floor when no admin setting has been saved yet', function () {
    expect(app(SaldoFloorService::class)->minimal())->toBe(100000);
});

it('persists the minimal saldo floor and reads it back through the service', function () {
    $service = app(SaldoFloorService::class);

    $service->simpan(50000);

    expect($service->minimal())->toBe(50000);
});

it('lets an admin update the minimal saldo floor through the Pengaturan Midtrans form', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Midtrans::class)
        ->assertSet('minimal_saldo_bayar_tagihan', 100000)
        ->set('server_key', 'test-server-key')
        ->set('client_key', 'test-client-key')
        ->set('minimal_saldo_bayar_tagihan', 50000)
        ->set('password_confirmasi', 'password')
        ->call('simpan')
        ->assertHasNoErrors();

    expect(app(SaldoFloorService::class)->minimal())->toBe(50000);
});

it('requires a non-negative integer for the minimal saldo floor', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Midtrans::class)
        ->set('server_key', 'test-server-key')
        ->set('client_key', 'test-client-key')
        ->set('minimal_saldo_bayar_tagihan', -1)
        ->set('password_confirmasi', 'password')
        ->call('simpan')
        ->assertHasErrors(['minimal_saldo_bayar_tagihan' => 'min']);
});

it('rejects saving Midtrans settings without the correct account password', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Midtrans::class)
        ->set('server_key', 'test-server-key')
        ->set('client_key', 'test-client-key')
        ->set('minimal_saldo_bayar_tagihan', 50000)
        ->set('password_confirmasi', 'wrong-password')
        ->call('simpan')
        ->assertHasErrors(['password_confirmasi']);
});

it('keeps the existing server key when the field is left blank on save', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Midtrans::class)
        ->set('server_key', 'original-server-key')
        ->set('client_key', 'test-client-key')
        ->set('password_confirmasi', 'password')
        ->call('simpan')
        ->assertHasNoErrors();

    Livewire::actingAs($admin)->test(Midtrans::class)
        ->assertSet('has_server_key', true)
        ->set('server_key', '')
        ->set('client_key', 'test-client-key-updated')
        ->set('password_confirmasi', 'password')
        ->call('simpan')
        ->assertHasNoErrors();

    expect(app(App\Services\MidtransSettingsService::class)->serverKey())->toBe('original-server-key');
});

it('defaults the Midtrans fee schedule to pondok-absorbed with zero fees on every channel', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Midtrans::class)
        ->assertSet('biaya_dibebankan_wali_topup', false)
        ->assertSet('biaya_dibebankan_wali_tagihan', false)
        ->assertSet('biaya_bni_va_tipe', MidtransFeeService::TIPE_TETAP)
        ->assertSet('biaya_bni_va_nilai', 0.0)
        ->assertSet('biaya_qris_tipe', MidtransFeeService::TIPE_TETAP)
        ->assertSet('biaya_qris_nilai', 0.0);
});

it('lets an admin configure and persist the Midtrans fee schedule', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Midtrans::class)
        ->set('server_key', 'test-server-key')
        ->set('client_key', 'test-client-key')
        ->set('biaya_dibebankan_wali_topup', true)
        ->set('biaya_dibebankan_wali_tagihan', false)
        ->set('biaya_bni_va_tipe', MidtransFeeService::TIPE_TETAP)
        ->set('biaya_bni_va_nilai', 4000)
        ->set('biaya_qris_tipe', MidtransFeeService::TIPE_PERSEN)
        ->set('biaya_qris_nilai', 0.7)
        ->set('password_confirmasi', 'password')
        ->call('simpan')
        ->assertHasNoErrors();

    $feeService = app(MidtransFeeService::class);

    expect($feeService->dibebankanWali(untukTagihan: false))->toBeTrue()
        ->and($feeService->dibebankanWali(untukTagihan: true))->toBeFalse()
        ->and($feeService->hitungBiaya(TopupWaliService::METODE_BNI_VA, 100000))->toBe(4000)
        ->and($feeService->hitungBiaya(TopupWaliService::METODE_QRIS, 100000))->toBe(700);
});

it('rejects a percentage fee above 100', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Midtrans::class)
        ->set('server_key', 'test-server-key')
        ->set('client_key', 'test-client-key')
        ->set('biaya_qris_tipe', MidtransFeeService::TIPE_PERSEN)
        ->set('biaya_qris_nilai', 150)
        ->set('password_confirmasi', 'password')
        ->call('simpan')
        ->assertHasErrors(['biaya_qris_nilai']);
});

it('sets a page-level success message immediately after saving, not via a session flash that needs a page reload', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Midtrans::class)
        ->assertSet('statusMessage', null)
        ->set('server_key', 'test-server-key')
        ->set('client_key', 'test-client-key')
        ->set('password_confirmasi', 'password')
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertSet('statusMessage', 'Pengaturan Midtrans berhasil disimpan.')
        ->assertSee('Pengaturan Midtrans berhasil disimpan.');
});

it('sets a page-level error message when the account password is wrong', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Midtrans::class)
        ->set('server_key', 'test-server-key')
        ->set('client_key', 'test-client-key')
        ->set('password_confirmasi', 'wrong-password')
        ->call('simpan')
        ->assertHasErrors(['password_confirmasi'])
        ->assertSet('statusMessage', null)
        ->assertSet('errorMessage', 'Gagal menyimpan. Periksa kembali isian yang ditandai di bawah.');
});

it('sets a page-level error message when Server Key is required but left blank on first setup', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Midtrans::class)
        ->set('server_key', '')
        ->set('client_key', 'test-client-key')
        ->set('password_confirmasi', 'password')
        ->call('simpan')
        ->assertHasErrors(['server_key'])
        ->assertSet('errorMessage', 'Gagal menyimpan. Server Key wajib diisi.');
});

it('snapshots the saved fee schedule separately from in-progress form edits, refreshing only after a successful save', function () {
    $admin = makeUserWithRole('admin');

    app(MidtransFeeService::class)->save(true, true, [
        TopupWaliService::METODE_BNI_VA => ['tipe' => 'tetap', 'nilai' => 4000],
        TopupWaliService::METODE_BCA_VA => ['tipe' => 'tetap', 'nilai' => 0],
        TopupWaliService::METODE_BRI_VA => ['tipe' => 'tetap', 'nilai' => 0],
        TopupWaliService::METODE_QRIS => ['tipe' => 'persen', 'nilai' => 0.7],
    ]);

    $component = Livewire::actingAs($admin)->test(Midtrans::class)
        ->assertSet('dibebankanWaliTopupTersimpan', true)
        ->assertSet('dibebankanWaliTagihanTersimpan', true)
        ->assertSee('BNI') // rendered inside the "tersimpan saat ini" summary
        // Editing the draft form must not touch the saved snapshot.
        ->set('biaya_bni_va_nilai', 9999)
        ->assertSet('biayaTersimpan.bni_va.nilai', 4000.0);

    $component
        ->set('server_key', 'test-server-key')
        ->set('client_key', 'test-client-key')
        ->set('password_confirmasi', 'password')
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertSet('biayaTersimpan.bni_va.nilai', 9999.0);
});
