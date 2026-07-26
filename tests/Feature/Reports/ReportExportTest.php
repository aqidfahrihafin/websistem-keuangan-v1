<?php

use App\Models\JenisTagihan;
use App\Models\Santri;
use App\Services\TagihanService;

it('exports santri to excel and pdf for an admin', function () {
    Santri::factory()->count(3)->create(['status' => Santri::STATUS_AKTIF]);
    $admin = makeUserWithRole('admin');

    $this->actingAs($admin)->get(route('admin.santri.export.excel'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($admin)->get(route('admin.santri.export.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('exports tagihan to excel and pdf honoring the periode filter', function () {
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    $jenis = JenisTagihan::factory()->create();
    $service = app(TagihanService::class);
    $service->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);

    $admin = makeUserWithRole('admin');

    $this->actingAs($admin)->get(route('admin.tagihan.export.excel', ['periode' => '2026-07']))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($admin)->get(route('admin.tagihan.export.pdf', ['periode' => '2026-07']))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('exports laporan santri to excel and pdf for a pengasuh', function () {
    Santri::factory()->count(2)->create(['status' => Santri::STATUS_AKTIF]);
    $pengasuh = makeUserWithRole('pengasuh');

    $this->actingAs($pengasuh)->get(route('pengasuh.laporan.export.excel'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($pengasuh)->get(route('pengasuh.laporan.export.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('forbids a non-admin from exporting the santri report', function () {
    $santriUser = makeUserWithRole('santri');

    $this->actingAs($santriUser)->get(route('admin.santri.export.excel'))->assertForbidden();
});
