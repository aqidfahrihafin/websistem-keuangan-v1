<?php

use App\Livewire\Admin\Pengaturan\Midtrans;
use App\Livewire\Pengasuh\PersetujuanMidtrans;
use App\Models\MidtransSettingApproval;
use App\Services\MidtransApprovalService;
use App\Services\MidtransSettingsService;
use Livewire\Livewire;

function usulanMidtrans(array $override = []): array
{
    $current = app(MidtransApprovalService::class)->current();

    return array_replace_recursive($current, [
        'server_key' => 'server-baru',
        'client_key' => 'client-baru',
    ], $override);
}

it('keeps active settings unchanged while an admin request awaits approval', function () {
    $admin = makeUserWithRole('admin');
    app(MidtransSettingsService::class)->save('server-lama', 'client-lama', false);

    Livewire::actingAs($admin)->test(Midtrans::class)
        ->set('server_key', 'server-baru')
        ->set('client_key', 'client-baru')
        ->set('password_confirmasi', 'password')
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertSee('menunggu persetujuan pengasuh');

    expect(app(MidtransSettingsService::class)->serverKey())->toBe('server-lama')
        ->and(MidtransSettingApproval::where('status', 'pending')->count())->toBe(1)
        ->and(MidtransSettingApproval::first()->changes['server_key']['new'])->toBe('Diubah');
});

it('lets a pengasuh approve and atomically activate a pending request', function () {
    $admin = makeUserWithRole('admin');
    $pengasuh = makeUserWithRole('pengasuh');
    app(MidtransSettingsService::class)->save('server-lama', 'client-lama', false);
    $approval = app(MidtransApprovalService::class)->request($admin, usulanMidtrans(['is_production' => true]));

    Livewire::actingAs($pengasuh)->test(PersetujuanMidtrans::class)
        ->assertSee('Pengajuan #'.$approval->id)
        ->assertDontSee('server-baru')
        ->set('passwordKonfirmasi', 'password')
        ->call('setujui', $approval->id)
        ->assertHasNoErrors()
        ->assertSee('konfigurasi baru sudah aktif');

    expect($approval->fresh()->status)->toBe(MidtransSettingApproval::STATUS_APPROVED)
        ->and($approval->fresh()->reviewed_by)->toBe($pengasuh->id)
        ->and(app(MidtransSettingsService::class)->serverKey())->toBe('server-baru')
        ->and(app(MidtransSettingsService::class)->isProduction())->toBeTrue();
});

it('requires the pengasuh password and a reason to reject', function () {
    $admin = makeUserWithRole('admin');
    $pengasuh = makeUserWithRole('pengasuh');
    $approval = app(MidtransApprovalService::class)->request($admin, usulanMidtrans());

    Livewire::actingAs($pengasuh)->test(PersetujuanMidtrans::class)
        ->set('passwordKonfirmasi', 'wrong-password')
        ->call('setujui', $approval->id)
        ->assertHasErrors('passwordKonfirmasi');

    expect($approval->fresh()->status)->toBe(MidtransSettingApproval::STATUS_PENDING);
});

it('rejects a request without changing active settings', function () {
    $admin = makeUserWithRole('admin');
    $pengasuh = makeUserWithRole('pengasuh');
    app(MidtransSettingsService::class)->save('server-lama', 'client-lama', false);
    $approval = app(MidtransApprovalService::class)->request($admin, usulanMidtrans());

    Livewire::actingAs($pengasuh)->test(PersetujuanMidtrans::class)
        ->set('passwordKonfirmasi', 'password')
        ->set('alasanPenolakan', 'Kredensial produksi belum diverifikasi.')
        ->call('tolak', $approval->id)
        ->assertHasNoErrors()
        ->assertSee('berhasil ditolak');

    expect($approval->fresh()->status)->toBe(MidtransSettingApproval::STATUS_REJECTED)
        ->and($approval->fresh()->review_note)->toBe('Kredensial produksi belum diverifikasi.')
        ->and(app(MidtransSettingsService::class)->serverKey())->toBe('server-lama');
});

it('expires old requests instead of allowing late approval', function () {
    $admin = makeUserWithRole('admin');
    $pengasuh = makeUserWithRole('pengasuh');
    $approval = app(MidtransApprovalService::class)->request($admin, usulanMidtrans());
    $approval->update(['expires_at' => now()->subMinute()]);

    expect(fn () => app(MidtransApprovalService::class)->approve($approval, $pengasuh))
        ->toThrow(RuntimeException::class, 'sudah kedaluwarsa');

    expect($approval->fresh()->status)->toBe(MidtransSettingApproval::STATUS_EXPIRED);
});

it('prevents stale requests from overwriting newer active configuration', function () {
    $admin = makeUserWithRole('admin');
    $pengasuh = makeUserWithRole('pengasuh');
    $approval = app(MidtransApprovalService::class)->request($admin, usulanMidtrans());
    app(MidtransSettingsService::class)->save('changed-elsewhere', 'client-lama', false);

    expect(fn () => app(MidtransApprovalService::class)->approve($approval, $pengasuh))
        ->toThrow(RuntimeException::class, 'sudah berubah sejak pengajuan');

    expect($approval->fresh()->status)->toBe(MidtransSettingApproval::STATUS_PENDING);
});

it('protects the approval page from non-pengasuh roles', function () {
    $admin = makeUserWithRole('admin');

    $this->actingAs($admin)->get(route('pengasuh.persetujuan-midtrans'))->assertForbidden();
});
