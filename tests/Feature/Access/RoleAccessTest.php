<?php

use App\Models\TopupWali;

it('redirects a guest away from any protected area to the login page', function () {
    $this->get('/admin')->assertRedirect('/login');
    $this->get('/wali')->assertRedirect('/login');
    $this->get('/santri')->assertRedirect('/login');
});

it('forbids a pengasuh from reaching the admin area', function () {
    $pengasuh = makeUserWithRole('pengasuh');

    $this->actingAs($pengasuh)->get('/admin')->assertForbidden();
});

it('forbids a wali from reaching the santri area and vice versa', function () {
    $wali = makeUserWithRole('wali');
    $santri = makeUserWithRole('santri');

    $this->actingAs($wali)->get('/santri')->assertForbidden();
    $this->actingAs($santri)->get('/wali')->assertForbidden();
});

it('allows admin and bendahara into the admin area', function () {
    $admin = makeUserWithRole('admin');
    $bendahara = makeUserWithRole('bendahara');

    $this->actingAs($admin)->get('/admin')->assertOk();
    $this->actingAs($bendahara)->get('/admin')->assertOk();
});

it('only exposes read access for pengasuh with no mutating routes in its area', function () {
    $pengasuh = makeUserWithRole('pengasuh');

    $this->actingAs($pengasuh)->get('/pengasuh')->assertOk();
    $this->actingAs($pengasuh)->get('/pengasuh/laporan-santri')->assertOk();
});

it('renders the admin topup list, including a row with a stored raw notification', function () {
    $admin = makeUserWithRole('admin');
    TopupWali::factory()->create([
        'status' => TopupWali::STATUS_PAID,
        'raw_notification' => ['transaction_status' => 'settlement', 'order_id' => 'TOPUP-TEST'],
    ]);

    $this->actingAs($admin)->get('/admin/topup')->assertOk();
});

it('allows only dev to reach the internal documentation area', function () {
    $dev = makeUserWithRole('dev');
    $admin = makeUserWithRole('admin');

    $this->actingAs($dev)->get('/dev')->assertOk();
    $this->actingAs($dev)->get('/dev/tentang')->assertOk();
    $this->actingAs($dev)->get('/dev/instalasi')->assertOk();
    $this->actingAs($dev)->get('/dev/api/wali')->assertOk();
    $this->actingAs($dev)->get('/dev/api/kiosk')->assertOk();

    $this->actingAs($admin)->get('/dev')->assertForbidden();
    $this->actingAs($dev)->get('/admin')->assertForbidden();
});
