<?php

it('keeps operational master data with admin while isolating system controls to superadmin', function () {
    $superadmin = makeUserWithRole('superadmin');
    $admin = makeUserWithRole('admin');
    $bendahara = makeUserWithRole('bendahara');
    // admin.wali.index queries User::role('wali'), which throws unless the
    // role has been created at least once - unrelated to what's under test
    // here, just a fixture prerequisite.
    makeUserWithRole('wali');

    $routes = [
        'admin.santri.index',
        'admin.keluarga.index',
        'admin.wali.index',
        'admin.kartu.index',
        'admin.lembaga.index',
        'admin.kamar.index',
        'admin.perangkat.index',
        'admin.kantin.index',
        'admin.kantin.penarikan.index',
        'admin.kantin.rekening.index',
        'admin.kantin.ledger.index',
    ];

    foreach ($routes as $routeName) {
        $this->actingAs($admin)->get(route($routeName))->assertOk();
        $this->actingAs($bendahara)->get(route($routeName))->assertForbidden();
    }

    $sensitiveRoutes = [
        'admin.users.index',
        'admin.pengaturan.aplikasi',
        'admin.pengaturan.maintenance',
        'admin.pengaturan.midtrans',
        'admin.backup.index',
    ];

    foreach ($sensitiveRoutes as $routeName) {
        $this->actingAs($superadmin)->get(route($routeName))->assertOk();
        $this->actingAs($admin)->get(route($routeName))->assertForbidden();
        $this->actingAs($bendahara)->get(route($routeName))->assertForbidden();
    }
});

it('still lets bendahara reach financial management pages', function () {
    $bendahara = makeUserWithRole('bendahara');

    $routes = [
        'admin.dashboard',
        'admin.tagihan.index',
        'admin.transaksi.index',
        'admin.topup.index',
        'admin.penarikan.index',
        'admin.laporan-keuangan.index',
        'admin.leger-kas-pondok.index',
    ];

    foreach ($routes as $routeName) {
        $this->actingAs($bendahara)->get(route($routeName))->assertOk();
    }
});
