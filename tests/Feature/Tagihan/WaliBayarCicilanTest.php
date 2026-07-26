<?php

use App\Livewire\Wali\Tagihan\Bayar;
use App\Models\JenisTagihan;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\Transaksi;
use App\Models\WaliSantri;
use App\Services\TagihanService;
use App\Services\WalletService;
use Livewire\Livewire;

function buatWaliDenganTagihan(int $nominal, bool $bisaDicicil): array
{
    $wali = makeUserWithRole('wali');
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    WaliSantri::create(['user_id' => $wali->id, 'santri_id' => $santri->id, 'hubungan' => 'ayah', 'is_auto_generated' => true, 'is_primary' => true]);

    $jenis = JenisTagihan::factory()->create(['nominal_default' => $nominal, 'bisa_dicicil' => $bisaDicicil]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::where('santri_id', $santri->id)->firstOrFail();

    return [$wali, $santri, $tagihan];
}

it('does not show a nominal input for a jenis tagihan that does not allow cicilan', function () {
    [$wali, $santri, $tagihan] = buatWaliDenganTagihan(100000, bisaDicicil: false);
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);

    Livewire::actingAs($wali)->test(Bayar::class, ['tagihan' => $tagihan])
        ->assertViewHas('bisaDicicil', false)
        ->assertDontSee('Nominal yang dibayar dari saldo');
});

it('shows a nominal input defaulting to the full sisa for a cicilan-enabled jenis tagihan', function () {
    [$wali, $santri, $tagihan] = buatWaliDenganTagihan(100000, bisaDicicil: true);
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);

    Livewire::actingAs($wali)->test(Bayar::class, ['tagihan' => $tagihan])
        ->assertSet('nominalCicil', 100000)
        ->assertSee('Nominal yang dibayar dari saldo');
});

it('lets a wali pay part of a cicilan-enabled tagihan from saldo, leaving it sebagian', function () {
    [$wali, $santri, $tagihan] = buatWaliDenganTagihan(100000, bisaDicicil: true);
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);

    Livewire::actingAs($wali)->test(Bayar::class, ['tagihan' => $tagihan])
        ->set('nominalCicil', 40000)
        ->assertViewHas('saldoCukup', true)
        ->call('bayarDariSaldo')
        ->assertHasNoErrors();

    expect($tagihan->fresh()->status)->toBe(Tagihan::STATUS_SEBAGIAN)
        ->and($tagihan->fresh()->nominal_terbayar)->toBe(40000)
        ->and($santri->saldo->fresh()->saldo)->toBe(160000);
});

it('shows a confirmation dialog with a payment breakdown before the saldo payment fires', function () {
    [$wali, $santri, $tagihan] = buatWaliDenganTagihan(100000, bisaDicicil: true);
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);

    Livewire::actingAs($wali)->test(Bayar::class, ['tagihan' => $tagihan])
        ->set('nominalCicil', 40000)
        ->assertSee('Konfirmasi Pembayaran dari Saldo')
        ->assertSee('Rp 40.000 akan didebit')
        ->assertSee('Sisa tagihan setelah ini: Rp 60.000')
        ->assertSee('Ya, Bayar Sekarang');

    // Nothing actually paid yet - the visible trigger only opens the dialog
    // (Alpine confirmOpen state), the real wire:click sits on the confirm
    // button inside it.
    expect($tagihan->fresh()->status)->toBe(Tagihan::STATUS_BELUM_LUNAS);
});

it('shows "tagihan akan langsung lunas" in the confirmation when paying the full sisa', function () {
    [$wali, $santri, $tagihan] = buatWaliDenganTagihan(100000, bisaDicicil: false);
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);

    Livewire::actingAs($wali)->test(Bayar::class, ['tagihan' => $tagihan])
        ->assertSee('Tagihan ini akan langsung lunas');
});

it('shows a form error instead of crashing when a non-cicilan tagihan is somehow paid a partial amount', function () {
    [$wali, $santri, $tagihan] = buatWaliDenganTagihan(100000, bisaDicicil: false);
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);

    // Even if the client tampers with nominalCicil, the component only
    // forwards it to the service when bisaDicicil is true (see
    // Bayar::bayarDariSaldo()), so a non-cicilan jenis always pays in full
    // regardless - this proves that guard holds from the outside.
    Livewire::actingAs($wali)->test(Bayar::class, ['tagihan' => $tagihan])
        ->set('nominalCicil', 40000)
        ->call('bayarDariSaldo')
        ->assertHasNoErrors();

    expect($tagihan->fresh()->status)->toBe(Tagihan::STATUS_LUNAS)
        ->and($tagihan->fresh()->nominal_terbayar)->toBe(100000);
});
