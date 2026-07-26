<?php

use App\Livewire\Admin\Kebijakan\KantinForm;
use App\Models\KebijakanKantin;
use Livewire\Livewire;

it('creates a kebijakan kantin', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(KantinForm::class)
        ->call('openCreate')
        ->set('nama', 'Uang Jajan MTs')
        ->set('limit_harian', 25000)
        ->set('effective_from', now()->toDateString())
        ->call('simpan')
        ->assertHasNoErrors();

    expect(KebijakanKantin::where('nama', 'Uang Jajan MTs')->exists())->toBeTrue();
});

it('toggles a kebijakan kantin active state', function () {
    $admin = makeUserWithRole('admin');
    $kebijakan = KebijakanKantin::factory()->create(['is_active' => true]);

    Livewire::actingAs($admin)->test(KantinForm::class)
        ->call('toggleActive', $kebijakan->id);

    expect($kebijakan->fresh()->is_active)->toBeFalse();
});

/**
 * Regression test for the exact bug found in Banner\Index (see its own
 * $editingId comment) - a public Eloquent-model Livewire property gets
 * re-fetched by primary key on every request, so deleting the row it
 * currently points to throws ModelNotFoundException on the very next
 * request. KantinForm was built with the safe $editingId (plain int)
 * pattern from the start; this proves the same real request/hydrate cycle
 * that broke Banner doesn't break this component too.
 */
it('does not crash when the kebijakan kantin currently open for editing is deleted', function () {
    $admin = makeUserWithRole('admin');
    $kebijakan = KebijakanKantin::factory()->create();

    $test = Livewire::actingAs($admin)->test(KantinForm::class);
    $test->call('openEdit', $kebijakan->id);
    $test->call('hapus', $kebijakan->id);
    // A follow-up request needs to hydrate the component again - this is
    // where the bug threw.
    $test->call('openCreate');

    expect(KebijakanKantin::count())->toBe(0);
});
