<?php

use App\Livewire\Admin\Tagihan\Index as TagihanIndex;
use App\Models\JenisTagihan;
use App\Models\Periode;
use App\Models\Santri;
use App\Services\TagihanService;
use Livewire\Livewire;

it('filters the tagihan list by periode', function () {
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    $jenis = JenisTagihan::factory()->create();
    $service = app(TagihanService::class);

    $service->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $service->generateTagihanForPeriode($jenis, '2026-08', null, null, null, [$santri->id]);

    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(TagihanIndex::class)
        ->assertViewHas('tagihans', fn ($tagihans) => $tagihans->count() === 2)
        ->set('periode', '2026-07')
        ->assertViewHas('tagihans', fn ($tagihans) => $tagihans->count() === 1 && $tagihans->first()->periode_label === '2026-07');
});

it('lists periode filter options from the Periode master table, including ones with no tagihan yet', function () {
    Periode::factory()->create(['label' => '2026-09']);
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(TagihanIndex::class)
        ->assertViewHas('periodeOptions', fn ($options) => $options->contains('2026-09'));
});

it('still lists a legacy periode_label with no matching Periode row so old tagihan stay filterable', function () {
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    $jenis = JenisTagihan::factory()->create();
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2025-01', null, null, null, [$santri->id]);

    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(TagihanIndex::class)
        ->assertViewHas('periodeOptions', fn ($options) => $options->contains('2025-01'))
        ->set('periode', '2025-01')
        ->assertViewHas('tagihans', fn ($tagihans) => $tagihans->count() === 1);
});
