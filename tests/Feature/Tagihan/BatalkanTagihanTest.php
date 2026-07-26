<?php

use App\Livewire\Admin\Tagihan\Index as TagihanIndex;
use App\Models\JenisTagihan;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\TagihanPembayaran;
use App\Models\Transaksi;
use App\Services\TagihanService;
use App\Services\WalletService;
use Livewire\Livewire;

function buatTagihanBelumDibayar(): Tagihan
{
    $santri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 150000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);

    return Tagihan::where('santri_id', $santri->id)->firstOrFail();
}

it('lets an admin cancel a tagihan that has never been paid', function () {
    $admin = makeUserWithRole('admin');
    $tagihan = buatTagihanBelumDibayar();

    Livewire::actingAs($admin)->test(TagihanIndex::class)
        ->call('bukaBatalkan', $tagihan->id)
        ->set('alasanPembatalan', 'Salah generate tagihan untuk santri ini.')
        ->set('passwordKonfirmasi', 'password')
        ->call('batalkanTagihan')
        ->assertHasNoErrors()
        ->assertSet('showBatalModal', false);

    $tagihan->refresh();
    expect($tagihan->status)->toBe('dibatalkan')
        ->and($tagihan->alasan_pembatalan)->toBe('Salah generate tagihan untuk santri ini.')
        ->and($tagihan->dibatalkan_oleh)->toBe($admin->id)
        ->and($tagihan->dibatalkan_at)->not->toBeNull();
});

it('requires a reason before cancelling a tagihan', function () {
    $admin = makeUserWithRole('admin');
    $tagihan = buatTagihanBelumDibayar();

    Livewire::actingAs($admin)->test(TagihanIndex::class)
        ->call('bukaBatalkan', $tagihan->id)
        ->set('alasanPembatalan', '')
        ->set('passwordKonfirmasi', 'password')
        ->call('batalkanTagihan')
        ->assertHasErrors(['alasanPembatalan']);

    expect($tagihan->fresh()->status)->toBe('belum_lunas');
});

it('rejects cancelling a tagihan with the wrong account password', function () {
    $admin = makeUserWithRole('admin');
    $tagihan = buatTagihanBelumDibayar();

    Livewire::actingAs($admin)->test(TagihanIndex::class)
        ->call('bukaBatalkan', $tagihan->id)
        ->set('alasanPembatalan', 'Alasan valid untuk pembatalan.')
        ->set('passwordKonfirmasi', 'salah')
        ->call('batalkanTagihan')
        ->assertHasErrors(['passwordKonfirmasi']);

    expect($tagihan->fresh()->status)->toBe('belum_lunas');
});

it('refuses to cancel a tagihan that already has a payment recorded', function () {
    $admin = makeUserWithRole('admin');
    $tagihan = buatTagihanBelumDibayar();

    app(TagihanService::class)->applyPembayaran($tagihan, 50000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);

    Livewire::actingAs($admin)->test(TagihanIndex::class)
        ->call('bukaBatalkan', $tagihan->id)
        ->set('alasanPembatalan', 'Coba batalkan meski sudah dibayar sebagian.')
        ->set('passwordKonfirmasi', 'password')
        ->call('batalkanTagihan')
        ->assertHasErrors(['alasanPembatalan']);

    expect($tagihan->fresh()->status)->toBe('sebagian');
});

it('does not show the Batalkan row action once a tagihan has a payment', function () {
    $admin = makeUserWithRole('admin');
    $tagihan = buatTagihanBelumDibayar();
    $santri = $tagihan->santri;
    app(WalletService::class)->credit($santri, 300000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);
    app(TagihanService::class)->bayarDariSaldo($tagihan, $admin);

    // The (always-rendered-but-hidden) modal's own title/button also say
    // "Batalkan", so this checks for the row action's wire:click call
    // specifically rather than a blanket "Batalkan" text search.
    Livewire::actingAs($admin)->test(TagihanIndex::class)
        ->assertDontSee('wire:click="bukaBatalkan', escape: false);
});

it('refuses to apply any payment to an already-cancelled tagihan, at the service layer', function () {
    $admin = makeUserWithRole('admin');
    $tagihan = buatTagihanBelumDibayar();
    $service = app(TagihanService::class);

    $service->batalkan($tagihan, $admin, 'Salah generate tagihan.');

    expect(fn () => $service->applyPembayaran($tagihan->fresh(), 50000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG))
        ->toThrow(InvalidArgumentException::class);

    expect($tagihan->fresh()->status)->toBe('dibatalkan')
        ->and($tagihan->fresh()->nominal_terbayar)->toBe(0);
});

it('lets a bendahara cancel a tagihan too, matching who can already manage tagihan', function () {
    $bendahara = makeUserWithRole('bendahara');
    $tagihan = buatTagihanBelumDibayar();

    Livewire::actingAs($bendahara)->test(TagihanIndex::class)
        ->call('bukaBatalkan', $tagihan->id)
        ->set('alasanPembatalan', 'Salah generate, dibatalkan oleh bendahara.')
        ->set('passwordKonfirmasi', 'password')
        ->call('batalkanTagihan')
        ->assertHasNoErrors();

    expect($tagihan->fresh()->status)->toBe('dibatalkan');
});
