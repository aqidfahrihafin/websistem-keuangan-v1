<?php

use App\Livewire\Admin\Topup\Index as TopupIndex;
use App\Models\JenisTagihan;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\TopupWali;
use App\Services\TagihanService;
use Livewire\Livewire;

it('badges a plain top up as "Top Up Saldo" and a tagihan-scoped payment as "Bayar Tagihan Langsung"', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create();

    $jenis = JenisTagihan::factory()->create(['nama' => 'SPP Bulanan']);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::where('santri_id', $santri->id)->firstOrFail();

    TopupWali::factory()->create(['santri_id' => $santri->id, 'midtrans_order_id' => 'TOPUP-AAA']);
    TopupWali::factory()->create(['santri_id' => $santri->id, 'tagihan_id' => $tagihan->id, 'midtrans_order_id' => 'TAGIHAN-BBB']);

    Livewire::actingAs($admin)->test(TopupIndex::class)
        ->assertSee('Top Up Saldo')
        ->assertSee('Bayar Tagihan Langsung')
        ->assertSee('SPP Bulanan');
});

it('filters the list to only tagihan-scoped payments or only plain top ups', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create();

    $jenis = JenisTagihan::factory()->create();
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::where('santri_id', $santri->id)->firstOrFail();

    $topup = TopupWali::factory()->create(['santri_id' => $santri->id]);
    $bayarTagihan = TopupWali::factory()->create(['santri_id' => $santri->id, 'tagihan_id' => $tagihan->id]);

    Livewire::actingAs($admin)->test(TopupIndex::class)
        ->set('jenis', 'tagihan')
        ->assertViewHas('topups', function ($topups) use ($bayarTagihan, $topup) {
            return $topups->pluck('id')->contains($bayarTagihan->id) && ! $topups->pluck('id')->contains($topup->id);
        })
        ->set('jenis', 'topup')
        ->assertViewHas('topups', function ($topups) use ($bayarTagihan, $topup) {
            return $topups->pluck('id')->contains($topup->id) && ! $topups->pluck('id')->contains($bayarTagihan->id);
        });
});

it('explains in the detail panel why a tagihan-scoped payment never appears in Riwayat Transaksi', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create();

    $jenis = JenisTagihan::factory()->create();
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::where('santri_id', $santri->id)->firstOrFail();
    $bayarTagihan = TopupWali::factory()->create(['santri_id' => $santri->id, 'tagihan_id' => $tagihan->id]);

    Livewire::actingAs($admin)->test(TopupIndex::class)
        ->call('toggleDetail', $bayarTagihan->id)
        ->assertSee('tidak pernah menyentuh saldo santri');
});

it('shows the recorded Midtrans fee and who bears it in the detail panel', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create();

    $topupWaliBorne = TopupWali::factory()->create([
        'santri_id' => $santri->id,
        'status' => TopupWali::STATUS_PAID,
        'biaya_midtrans' => 4000,
        'biaya_ditanggung_wali' => true,
    ]);

    Livewire::actingAs($admin)->test(TopupIndex::class)
        ->call('toggleDetail', $topupWaliBorne->id)
        ->assertSee('Rp 4.000')
        ->assertSee('Wali');

    $topupPondokBorne = TopupWali::factory()->create([
        'santri_id' => $santri->id,
        'status' => TopupWali::STATUS_PAID,
        'biaya_midtrans' => 3500,
        'biaya_ditanggung_wali' => false,
    ]);

    Livewire::actingAs($admin)->test(TopupIndex::class)
        ->call('toggleDetail', $topupPondokBorne->id)
        ->assertSee('Rp 3.500')
        ->assertSee('Pondok');
});
