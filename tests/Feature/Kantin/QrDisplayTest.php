<?php

use App\Livewire\Admin\Kantin\Index as AdminKantinIndex;
use App\Models\UnitUsaha;
use Livewire\Livewire;

it('renders the pengelola QR page with the kantin name and kode', function () {
    [$unit, $pengelola] = buatPengelola();

    $this->actingAs($pengelola)->get('/pengelola/qr')
        ->assertOk()
        ->assertSee($unit->nama)
        ->assertSee($unit->kode);
});

it('renders the admin Kelola Kantin QR modal with the kantin name and kode', function () {
    $admin = makeUserWithRole('admin');
    $unit = UnitUsaha::factory()->create();

    Livewire::actingAs($admin)->test(AdminKantinIndex::class)
        ->call('bukaQr', $unit->id)
        ->assertSee($unit->nama)
        ->assertSee($unit->kode)
        ->assertSet('showQr', true);
});
