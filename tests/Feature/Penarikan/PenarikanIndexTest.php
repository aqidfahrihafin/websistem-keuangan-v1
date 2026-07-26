<?php

use App\Livewire\Admin\Penarikan\Index as PenarikanIndex;
use App\Models\Device;
use App\Models\PenarikanRequest;
use App\Models\Santri;
use Livewire\Livewire;

it('defaults to showing requests of every status, not just menunggu', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create();

    $menunggu = PenarikanRequest::create([
        'santri_id' => $santri->id, 'nominal_diminta' => 10000, 'status' => PenarikanRequest::STATUS_MENUNGGU,
        'diminta_at' => now(), 'dalam_jam_kebijakan' => true, 'melebihi_limit_harian' => false, 'wajib_surat_keterangan' => false,
    ]);
    $selesai = PenarikanRequest::create([
        'santri_id' => $santri->id, 'nominal_diminta' => 20000, 'status' => PenarikanRequest::STATUS_SELESAI,
        'diminta_at' => now(), 'dalam_jam_kebijakan' => true, 'melebihi_limit_harian' => false, 'wajib_surat_keterangan' => false,
    ]);

    Livewire::actingAs($admin)->test(PenarikanIndex::class)
        ->assertSet('status', '')
        ->assertViewHas('requests', function ($requests) use ($menunggu, $selesai) {
            return $requests->pluck('id')->contains($menunggu->id) && $requests->pluck('id')->contains($selesai->id);
        });
});

it('surfaces the originating device/location for audit, or a dash when none', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create();
    $device = Device::factory()->create(['nama' => 'Kiosk Aula Utama', 'lokasi' => 'Aula Utama']);

    $viaKiosk = PenarikanRequest::create([
        'santri_id' => $santri->id, 'nominal_diminta' => 10000, 'status' => PenarikanRequest::STATUS_SELESAI,
        'diminta_at' => now(), 'dalam_jam_kebijakan' => true, 'melebihi_limit_harian' => false, 'wajib_surat_keterangan' => false,
        'device_id' => $device->id,
    ]);
    $viaAdmin = PenarikanRequest::create([
        'santri_id' => $santri->id, 'nominal_diminta' => 20000, 'status' => PenarikanRequest::STATUS_SELESAI,
        'diminta_at' => now(), 'dalam_jam_kebijakan' => true, 'melebihi_limit_harian' => false, 'wajib_surat_keterangan' => false,
    ]);

    $html = Livewire::actingAs($admin)->test(PenarikanIndex::class)->html();

    expect($html)->toContain('Kiosk Aula Utama - Aula Utama');

    $requests = Livewire::actingAs($admin)->test(PenarikanIndex::class)
        ->viewData('requests');
    expect($requests->firstWhere('id', $viaKiosk->id)->device->nama)->toBe('Kiosk Aula Utama')
        ->and($requests->firstWhere('id', $viaAdmin->id)->device)->toBeNull();
});
