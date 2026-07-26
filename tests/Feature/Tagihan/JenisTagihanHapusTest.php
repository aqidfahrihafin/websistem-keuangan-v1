<?php

use App\Livewire\Admin\Tagihan\JenisIndex;
use App\Models\JenisTagihan;
use App\Models\Santri;
use App\Services\TagihanService;
use Livewire\Livewire;

it('deletes a jenis tagihan that has never had any tagihan generated for it', function () {
    $admin = makeUserWithRole('admin');
    $jenis = JenisTagihan::factory()->create();

    Livewire::actingAs($admin)->test(JenisIndex::class)
        ->call('hapus', $jenis->id)
        ->assertSet('errorHapus', null);

    expect(JenisTagihan::find($jenis->id))->toBeNull();
});

it('refuses to delete a jenis tagihan that already has generated tagihan', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 50000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);

    Livewire::actingAs($admin)->test(JenisIndex::class)
        ->call('hapus', $jenis->id)
        ->assertSet('errorHapus', fn (?string $pesan) => str_contains($pesan, 'tagihan'));

    expect(JenisTagihan::find($jenis->id))->not->toBeNull();
});

it('lets bendahara delete a jenis tagihan but forbids wali from accessing the page', function () {
    $bendahara = makeUserWithRole('bendahara');
    $jenis = JenisTagihan::factory()->create();

    Livewire::actingAs($bendahara)->test(JenisIndex::class)
        ->call('hapus', $jenis->id);

    expect(JenisTagihan::find($jenis->id))->toBeNull();

    $wali = makeUserWithRole('wali');
    $this->actingAs($wali)->get(route('admin.tagihan.jenis.index'))->assertForbidden();
});
