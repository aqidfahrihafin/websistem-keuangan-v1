<?php

use App\Livewire\Santri\Penarikan\Request as PenarikanRequestPage;
use App\Models\KebijakanPenarikan;
use App\Models\Santri;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

afterEach(function () {
    Carbon::setTestNow();
});

it('shows the daily withdrawal limit summary for a santri with an active kebijakan', function () {
    KebijakanPenarikan::factory()->create([
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '15:00:00',
        'limit_harian' => 50000,
        'is_active' => true,
        'effective_from' => '2020-01-01',
    ]);
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santriUser = makeUserWithRole('santri');
    Santri::factory()->create(['status' => Santri::STATUS_AKTIF, 'user_id' => $santriUser->id]);

    Livewire::actingAs($santriUser)->test(PenarikanRequestPage::class)
        ->assertViewHas('limitInfo', fn (array $info) => $info['limit'] === 50000
            && $info['sisa'] === 50000
            && $info['dalam_jam'] === true
            && $info['kebijakan'] instanceof KebijakanPenarikan);
});

it('shows no limit info when the santri account is not linked to a santri record', function () {
    $santriUser = makeUserWithRole('santri');

    Livewire::actingAs($santriUser)->test(PenarikanRequestPage::class)
        ->assertViewHas('limitInfo', null)
        ->assertOk();
});
