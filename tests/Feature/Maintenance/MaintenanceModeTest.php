<?php

use App\Services\MaintenanceModeService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('system.maintenance.status');
    Cache::forget('system.maintenance.status.v2');
});

it('stores only scalar dates in cache for shared hosting compatibility', function () {
    $admin = makeUserWithRole('admin', ['must_change_password' => false]);
    app(MaintenanceModeService::class)->activate(
        'Pemeliharaan keamanan sistem sedang berlangsung.',
        now()->addHour(),
        $admin,
    );

    $status = app(MaintenanceModeService::class)->status();
    $cached = Cache::get('system.maintenance.status.v2');

    expect($status['started_at'])->toBeInstanceOf(\Illuminate\Support\Carbon::class)
        ->and($cached['started_at'])->toBeString()
        ->and($cached['expected_end_at'])->toBeString();
});

it('publishes a lightweight system status for the mobile app', function () {
    $this->getJson('/api/system/status')
        ->assertOk()
        ->assertJsonPath('data.maintenance', false);
});

it('blocks web and api users while preserving structured maintenance details', function () {
    $admin = makeUserWithRole('admin', ['must_change_password' => false]);
    $wali = makeUserWithRole('wali', ['must_change_password' => false]);

    app(MaintenanceModeService::class)->activate(
        'Pemeliharaan keamanan sistem sedang berlangsung.',
        now()->addHour(),
        $admin,
    );

    $this->actingAs($wali)
        ->get('/wali')
        ->assertStatus(503)
        ->assertSee('Layanan sedang kami siapkan kembali');

    $this->actingAs($wali)
        ->getJson('/api/wali/app-info')
        ->assertStatus(503)
        ->assertHeader('Retry-After', '60')
        ->assertJsonPath('code', 'maintenance_mode')
        ->assertJsonPath('maintenance.maintenance', true);
});

it('keeps authenticated superadmin as the audited recovery path', function () {
    $admin = makeUserWithRole('superadmin', ['must_change_password' => false]);
    app(MaintenanceModeService::class)->activate(
        'Pemeliharaan keamanan sistem sedang berlangsung.',
        null,
        $admin,
    );

    $this->actingAs($admin)
        ->get('/admin/pengaturan/maintenance')
        ->assertOk()
        ->assertSee('Maintenance sedang aktif')
        ->assertSee('Buka kembali seluruh layanan?')
        ->assertSee(route('maintenance.end'));

    $this->actingAs($admin)
        ->get('/admin/transaksi')
        ->assertStatus(503);

    app(MaintenanceModeService::class)->deactivate($admin);

    expect(app(MaintenanceModeService::class)->active())->toBeFalse();
    $this->assertDatabaseHas('activity_log', [
        'log_name' => 'maintenance',
        'description' => 'Mode maintenance dinonaktifkan',
    ]);
});

it('always leaves the mobile status endpoint reachable during maintenance', function () {
    $admin = makeUserWithRole('admin', ['must_change_password' => false]);
    app(MaintenanceModeService::class)->activate(
        'Pemeliharaan keamanan sistem sedang berlangsung.',
        null,
        $admin,
    );

    $this->getJson('/api/system/status')
        ->assertOk()
        ->assertJsonPath('data.maintenance', true)
        ->assertJsonPath('data.message', 'Pemeliharaan keamanan sistem sedang berlangsung.');
});

it('replaces the regular login with maintenance information', function () {
    $admin = makeUserWithRole('admin', [
        'email' => 'recovery-admin@example.test',
        'password' => bcrypt('secret-password'),
        'must_change_password' => false,
    ]);
    app(MaintenanceModeService::class)->activate(
        'Pemeliharaan keamanan sistem sedang berlangsung.',
        null,
        $admin,
    );

    $this->get('/login')
        ->assertStatus(503)
        ->assertSee('Layanan sedang kami siapkan kembali')
        ->assertSee(route('maintenance.admin-login'));
});

it('provides a non Livewire recovery login that only accepts superadmin', function () {
    $admin = makeUserWithRole('superadmin', [
        'email' => 'direct-recovery@example.test',
        'password' => bcrypt('secret-password'),
        'must_change_password' => false,
    ]);
    $wali = makeUserWithRole('wali', [
        'email' => 'direct-blocked@example.test',
        'password' => bcrypt('secret-password'),
        'must_change_password' => false,
    ]);
    app(MaintenanceModeService::class)->activate(
        'Pemeliharaan keamanan sistem sedang berlangsung.',
        null,
        $admin,
    );

    $this->get('/maintenance/admin-login')
        ->assertOk()
        ->assertSee('Login khusus admin');

    $this->post('/maintenance/admin-login', [
        'login' => $wali->email,
        'password' => 'secret-password',
    ])->assertSessionHasErrors('login');
    $this->assertGuest();

    $this->post('/maintenance/admin-login', [
        'login' => $admin->email,
        'password' => 'secret-password',
    ])->assertRedirect(route('admin.pengaturan.maintenance'));
    $this->assertAuthenticatedAs($admin);
    $this->assertTrue(session('maintenance.admin_recovery'));

    $this->post('/maintenance/end')
        ->assertRedirect(route('admin.dashboard'));
    expect(app(MaintenanceModeService::class)->active())->toBeFalse();
    $this->assertNull(session('maintenance.admin_recovery'));
});
