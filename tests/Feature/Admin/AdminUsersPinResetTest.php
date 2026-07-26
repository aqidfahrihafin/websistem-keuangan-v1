<?php

use App\Livewire\Admin\Users\Index;
use App\Services\PinService;
use Livewire\Livewire;

it('lets an admin reset a wali\'s transaction pin', function () {
    $admin = makeUserWithRole('admin');
    $wali = makeUserWithRole('wali');
    app(PinService::class)->set($wali, '246810');

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openEdit', $wali->id)
        ->assertSee('Reset PIN')
        ->call('resetPin', $wali->id)
        ->assertDontSee('Reset PIN');

    expect($wali->fresh()->hasPin())->toBeFalse();
});

it('does not show the reset pin action for a wali with no pin set', function () {
    $admin = makeUserWithRole('admin');
    $wali = makeUserWithRole('wali');

    Livewire::actingAs($admin)
        ->test(Index::class)
        ->call('openEdit', $wali->id)
        ->assertDontSee('Reset PIN');
});
