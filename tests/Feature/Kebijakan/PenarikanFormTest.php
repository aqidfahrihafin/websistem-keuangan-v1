<?php

use App\Livewire\Admin\Kebijakan\PenarikanForm;
use App\Models\KebijakanPenarikan;
use App\Models\Lembaga;
use Livewire\Livewire;

it('creates a kebijakan penarikan', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(PenarikanForm::class)
        ->call('openCreate')
        ->set('nama', 'Kebijakan Reguler')
        ->set('jam_mulai', '08:00')
        ->set('jam_selesai', '15:00')
        ->set('limit_harian', 100000)
        ->set('effective_from', now()->toDateString())
        ->call('simpan')
        ->assertHasNoErrors();

    expect(KebijakanPenarikan::where('nama', 'Kebijakan Reguler')->exists())->toBeTrue();
});

it('loads an existing kebijakan into the form and updates it in place', function () {
    $admin = makeUserWithRole('admin');
    $lembaga = Lembaga::factory()->create();
    $kebijakan = KebijakanPenarikan::factory()->create([
        'nama' => 'Kebijakan Lama',
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '15:00:00',
        'limit_harian' => 50000,
    ]);

    Livewire::actingAs($admin)->test(PenarikanForm::class)
        ->call('openEdit', $kebijakan->id)
        ->assertSet('nama', 'Kebijakan Lama')
        ->assertSet('jam_mulai', '08:00')
        ->assertSet('jam_selesai', '15:00')
        ->set('nama', 'Kebijakan Baru')
        ->set('limit_harian', 75000)
        ->set('applies_lembaga_id', $lembaga->id)
        ->call('simpan')
        ->assertHasNoErrors();

    expect(KebijakanPenarikan::count())->toBe(1)
        ->and($kebijakan->fresh()->nama)->toBe('Kebijakan Baru')
        ->and($kebijakan->fresh()->limit_harian)->toBe(75000)
        ->and($kebijakan->fresh()->applies_lembaga_id)->toBe($lembaga->id);
});

it('deletes a kebijakan penarikan', function () {
    $admin = makeUserWithRole('admin');
    $kebijakan = KebijakanPenarikan::factory()->create();

    Livewire::actingAs($admin)->test(PenarikanForm::class)
        ->call('hapus', $kebijakan->id);

    expect(KebijakanPenarikan::find($kebijakan->id))->toBeNull();
});
