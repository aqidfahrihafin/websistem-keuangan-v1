<?php

use App\Livewire\Admin\Periode\Index as PeriodeIndex;
use App\Models\Periode;
use Livewire\Livewire;

it('auto-activates the first periode ever created, then lets admin activate another exclusively', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(PeriodeIndex::class)
        ->call('openCreate')
        ->assertSet('showModal', true)
        ->set('label', '2026-07')
        ->call('save')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    $juli = Periode::where('label', '2026-07')->firstOrFail();
    expect($juli->is_active)->toBeTrue();

    Livewire::actingAs($admin)->test(PeriodeIndex::class)
        ->call('openCreate')
        ->set('label', '2026-08')
        ->call('save');

    $agustus = Periode::where('label', '2026-08')->firstOrFail();
    expect($agustus->is_active)->toBeFalse()
        ->and($juli->fresh()->is_active)->toBeTrue();

    Livewire::actingAs($admin)->test(PeriodeIndex::class)
        ->call('aktifkan', $agustus->id);

    expect($agustus->fresh()->is_active)->toBeTrue()
        ->and($juli->fresh()->is_active)->toBeFalse();
});

it('rejects a duplicate periode label', function () {
    $admin = makeUserWithRole('admin');
    Periode::factory()->create(['label' => '2026-07']);

    Livewire::actingAs($admin)->test(PeriodeIndex::class)
        ->call('openCreate')
        ->set('label', '2026-07')
        ->call('save')
        ->assertHasErrors(['label']);
});

it('lets an admin edit an existing periode', function () {
    $admin = makeUserWithRole('admin');
    $periode = Periode::factory()->create(['label' => '2026-07', 'tanggal_mulai' => '2026-07-01']);

    Livewire::actingAs($admin)->test(PeriodeIndex::class)
        ->call('openEdit', $periode->id)
        ->assertSet('showModal', true)
        ->assertSet('label', '2026-07')
        ->set('label', '2026-07 (Revisi)')
        ->call('save')
        ->assertSet('showModal', false)
        ->assertHasNoErrors();

    expect($periode->fresh()->label)->toBe('2026-07 (Revisi)');
});

it('lets an admin delete a periode that has not ended yet', function () {
    $admin = makeUserWithRole('admin');
    $periode = Periode::factory()->create(['label' => '2026-07', 'tanggal_selesai' => today()->addMonth()]);

    Livewire::actingAs($admin)->test(PeriodeIndex::class)
        ->call('hapus', $periode->id);

    expect(Periode::find($periode->id))->toBeNull();
});

it('refuses to delete a periode whose tanggal_selesai has passed', function () {
    $admin = makeUserWithRole('admin');
    $periode = Periode::factory()->create(['label' => '2026-05', 'tanggal_selesai' => today()->subDay()]);

    Livewire::actingAs($admin)->test(PeriodeIndex::class)
        ->call('hapus', $periode->id);

    expect(Periode::find($periode->id))->not->toBeNull();
});

it('still allows deleting a periode whose tanggal_selesai is exactly today', function () {
    $admin = makeUserWithRole('admin');
    $periode = Periode::factory()->create(['label' => '2026-06', 'tanggal_selesai' => today()]);

    Livewire::actingAs($admin)->test(PeriodeIndex::class)
        ->call('hapus', $periode->id);

    expect(Periode::find($periode->id))->toBeNull();
});

it('auto-deactivates an active periode whose tanggal_selesai has passed when the list renders', function () {
    $admin = makeUserWithRole('admin');
    $expired = Periode::factory()->create(['label' => '2026-05', 'is_active' => true, 'tanggal_selesai' => today()->subDay()]);

    Livewire::actingAs($admin)->test(PeriodeIndex::class);

    expect($expired->fresh()->is_active)->toBeFalse();
});

it('syncs expired periode via the scheduled artisan command', function () {
    $expired = Periode::factory()->create(['is_active' => true, 'tanggal_selesai' => today()->subDay()]);
    $stillActive = Periode::factory()->create(['is_active' => true, 'tanggal_selesai' => today()->addDay()]);

    $this->artisan('periode:sync-expired')->assertSuccessful();

    expect($expired->fresh()->is_active)->toBeFalse()
        ->and($stillActive->fresh()->is_active)->toBeTrue();
});
