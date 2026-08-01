<?php

use Illuminate\Support\Facades\Route;

it('tidak menyediakan jalur setoran tunai langsung untuk admin', function () {
    $admin = makeUserWithRole('admin');

    expect(Route::has('admin.transaksi.verifikasi'))->toBeFalse();

    $this->actingAs($admin)
        ->get('/admin/transaksi/verifikasi')
        ->assertNotFound();
});

it('tidak menampilkan aksi setoran tunai langsung pada halaman transaksi admin', function () {
    $admin = makeUserWithRole('admin');

    $this->actingAs($admin)
        ->get(route('admin.transaksi.index'))
        ->assertOk()
        ->assertDontSee('Catat Setoran Tunai');
});
