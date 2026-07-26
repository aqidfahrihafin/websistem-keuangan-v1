<?php

use App\Livewire\Admin\Tagihan\Index as TagihanIndex;
use App\Models\JenisTagihan;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\TagihanPembayaran;
use App\Services\TagihanService;
use Livewire\Livewire;

it('shows a payment-source badge on a lunas tagihan paid in a single source', function () {
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 50000]);
    $service = app(TagihanService::class);

    $service->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::where('santri_id', $santri->id)->where('periode_label', '2026-07')->firstOrFail();
    $service->applyPembayaran($tagihan, 50000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);

    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(TagihanIndex::class)
        ->assertSee('Tunai Langsung');
});

it('shows every distinct payment source once when a tagihan was paid through a mix of sources', function () {
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    $service = app(TagihanService::class);

    $service->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::where('santri_id', $santri->id)->where('periode_label', '2026-07')->firstOrFail();
    $service->applyPembayaran($tagihan, 40000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);
    $service->applyPembayaran($tagihan, 30000, TagihanPembayaran::SUMBER_SALDO);
    $service->applyPembayaran($tagihan, 30000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);

    $admin = makeUserWithRole('admin');

    $html = Livewire::actingAs($admin)->test(TagihanIndex::class)->html();

    expect(substr_count($html, 'Tunai Langsung'))->toBe(1)
        ->and($html)->toContain('Saldo');
});

it('does not show a payment-source badge for a tagihan that is not yet lunas', function () {
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    $service = app(TagihanService::class);

    $service->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::where('santri_id', $santri->id)->where('periode_label', '2026-07')->firstOrFail();
    $service->applyPembayaran($tagihan, 40000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);

    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(TagihanIndex::class)
        ->assertDontSee('Tunai Langsung');
});
