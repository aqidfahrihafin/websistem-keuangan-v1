<?php

use App\Livewire\Admin\Perangkat\Index as PerangkatIndex;
use App\Models\Device;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('creates and edits a device through the modal', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(PerangkatIndex::class)
        ->call('openCreate')
        ->set('kode_device', 'KIOSK-AULA-01')
        ->set('nama', 'Kiosk Penarikan Aula Utama')
        ->set('lokasi', 'Aula Utama')
        ->set('tipe', Device::TIPE_KIOSK_PENARIKAN)
        ->call('save')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    $device = Device::where('kode_device', 'KIOSK-AULA-01')->firstOrFail();
    expect($device->nama)->toBe('Kiosk Penarikan Aula Utama')
        ->and($device->tipe)->toBe(Device::TIPE_KIOSK_PENARIKAN)
        ->and($device->status)->toBe('aktif');

    Livewire::actingAs($admin)->test(PerangkatIndex::class)
        ->call('openEdit', $device->id)
        ->assertSet('kode_device', 'KIOSK-AULA-01')
        ->assertSet('nama', 'Kiosk Penarikan Aula Utama')
        ->set('nama', 'Kiosk Penarikan Aula Utama (Updated)')
        ->call('save')
        ->assertSet('showModal', false);

    expect($device->fresh()->nama)->toBe('Kiosk Penarikan Aula Utama (Updated)');
});

it('rejects a kode_device with characters unsafe for a URL segment', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(PerangkatIndex::class)
        ->call('openCreate')
        ->set('kode_device', 'kiosk aula/01')
        ->set('nama', 'Kiosk Tidak Valid')
        ->call('save')
        ->assertHasErrors('kode_device');

    expect(Device::where('nama', 'Kiosk Tidak Valid')->exists())->toBeFalse();
});

it('rejects a duplicate kode_device', function () {
    $admin = makeUserWithRole('admin');
    Device::factory()->create(['kode_device' => 'KIOSK-01']);

    Livewire::actingAs($admin)->test(PerangkatIndex::class)
        ->call('openCreate')
        ->set('kode_device', 'KIOSK-01')
        ->set('nama', 'Kiosk Duplikat')
        ->call('save')
        ->assertHasErrors('kode_device');
});

it('toggles a device between aktif and nonaktif', function () {
    $admin = makeUserWithRole('admin');
    $device = Device::factory()->create(['status' => 'aktif']);

    Livewire::actingAs($admin)->test(PerangkatIndex::class)
        ->call('toggleActive', $device->id);

    expect($device->fresh()->status)->toBe('nonaktif');

    Livewire::actingAs($admin)->test(PerangkatIndex::class)
        ->call('toggleActive', $device->id);

    expect($device->fresh()->status)->toBe('aktif');
});

it('shows a last-seen label derived from last_seen_at without a dedicated column', function () {
    $admin = makeUserWithRole('admin');
    Carbon::setTestNow(Carbon::parse('2026-07-13 12:00:00'));

    $belumPernah = Device::factory()->create(['nama' => 'Belum Dipakai', 'last_seen_at' => null]);
    $online = Device::factory()->create(['nama' => 'Online', 'last_seen_at' => now()->subMinutes(2)]);
    $lama = Device::factory()->create(['nama' => 'Lama', 'last_seen_at' => now()->subDays(3)]);

    Livewire::actingAs($admin)->test(PerangkatIndex::class)
        ->assertViewHas('statusTerakhir', function ($labels) use ($belumPernah, $online, $lama) {
            return $labels[$belumPernah->id] === 'Belum pernah digunakan'
                && $labels[$online->id] === 'Online'
                && str_contains($labels[$lama->id], 'Terakhir aktif');
        });

    Carbon::setTestNow();
});

it('lets an admin claim petugas jaga on a device, replacing whoever was there before', function () {
    $admin1 = makeUserWithRole('admin');
    $admin2 = makeUserWithRole('admin');
    $device = Device::factory()->create();

    Livewire::actingAs($admin1)->test(PerangkatIndex::class)
        ->call('jagaDisini', $device->id);

    expect($device->fresh()->petugas_jaga_id)->toBe($admin1->id)
        ->and($device->fresh()->petugas_jaga_sejak)->not->toBeNull();

    Livewire::actingAs($admin2)->test(PerangkatIndex::class)
        ->call('jagaDisini', $device->id);

    expect($device->fresh()->petugas_jaga_id)->toBe($admin2->id);
});

it('lets petugas jaga be released, leaving the device unassigned', function () {
    $admin = makeUserWithRole('admin');
    $device = Device::factory()->create(['petugas_jaga_id' => $admin->id, 'petugas_jaga_sejak' => now()]);

    Livewire::actingAs($admin)->test(PerangkatIndex::class)
        ->call('lepasJaga', $device->id);

    expect($device->fresh()->petugas_jaga_id)->toBeNull()
        ->and($device->fresh()->petugas_jaga_sejak)->toBeNull();
});

it('opens a setup guide showing the kiosk URL and the RFID/browser steps for any device', function () {
    $admin = makeUserWithRole('admin');
    $device = Device::factory()->create(['kode_device' => 'KIOSK-SALDO-01', 'tipe' => Device::TIPE_KIOSK_SALDO]);

    Livewire::actingAs($admin)->test(PerangkatIndex::class)
        ->call('bukaPanduan', $device->id)
        ->assertSet('showPanduan', true)
        ->assertSet('panduan.id', $device->id)
        ->assertSee('kios/KIOSK-SALDO-01')
        ->assertSee('RFID reader')
        ->assertSee('mode kiosk')
        ->assertDontSee('Pasang terminal fingerprint');
});

it('includes the fingerprint enrollment step for withdrawal and canteen kiosks', function () {
    $admin = makeUserWithRole('admin');

    foreach ([Device::TIPE_KIOSK_PENARIKAN, Device::TIPE_KANTIN] as $tipe) {
        $device = Device::factory()->create(['tipe' => $tipe]);

        Livewire::actingAs($admin)->test(PerangkatIndex::class)
            ->call('bukaPanduan', $device->id)
            ->assertSee('Pasang terminal fingerprint')
            ->assertSee('Referensi Sidik Jari');
    }
});

it('is only reachable by admin, not bendahara or wali', function () {
    $wali = makeUserWithRole('wali');
    $this->actingAs($wali)->get(route('admin.perangkat.index'))->assertForbidden();

    // Perangkat is system administration (which physical kiosk exists,
    // where), not financial management - bendahara's scope is Keuangan,
    // so it's admin-only just like Users/Lembaga/Pengaturan.
    $bendahara = makeUserWithRole('bendahara');
    $this->actingAs($bendahara)->get(route('admin.perangkat.index'))->assertForbidden();

    $admin = makeUserWithRole('admin');
    $this->actingAs($admin)->get(route('admin.perangkat.index'))->assertOk();
});
