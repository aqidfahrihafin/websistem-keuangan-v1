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
        ->assertSee('Kami segera kembali');

    $this->actingAs($wali)
        ->getJson('/api/wali/app-info')
        ->assertStatus(503)
        ->assertHeader('Retry-After', '60')
        ->assertJsonPath('code', 'maintenance_mode')
        ->assertJsonPath('maintenance.maintenance', true);
});

it('keeps authenticated administrators as the audited recovery path', function () {
    $admin = makeUserWithRole('admin', ['must_change_password' => false]);
    app(MaintenanceModeService::class)->activate(
        'Pemeliharaan keamanan sistem sedang berlangsung.',
        null,
        $admin,
    );

    $this->actingAs($admin)
        ->get('/admin/pengaturan/maintenance')
        ->assertOk()
        ->assertSee('Maintenance sedang aktif');

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
