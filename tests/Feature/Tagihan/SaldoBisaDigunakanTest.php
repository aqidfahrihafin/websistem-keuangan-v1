<?php

use App\Livewire\Wali\Saldo as WaliSaldo;
use App\Livewire\Wali\Tagihan\Bayar;
use App\Models\JenisTagihan;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\Transaksi;
use App\Models\WaliSantri;
use App\Services\SaldoFloorService;
use App\Services\TagihanService;
use App\Services\WalletService;
use Livewire\Livewire;

function buatWaliDenganSantriBersaldo(int $saldo): array
{
    $wali = makeUserWithRole('wali');
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    WaliSantri::create(['user_id' => $wali->id, 'santri_id' => $santri->id, 'hubungan' => 'ayah', 'is_auto_generated' => true, 'is_primary' => true]);

    if ($saldo > 0) {
        app(WalletService::class)->credit($santri, $saldo, Transaksi::JENIS_TOPUP_TUNAI);
    }

    return [$wali, $santri];
}

it('shows saldo bisa digunakan (saldo minus batas minimum) on the wali saldo page', function () {
    app(SaldoFloorService::class)->simpan(20000);
    [$wali, $santri] = buatWaliDenganSantriBersaldo(100000);

    Livewire::actingAs($wali)->test(WaliSaldo::class)
        ->assertViewHas('saldoBisaDigunakan', 80000)
        ->assertSee('Saldo bisa digunakan')
        ->assertSee('80.000');
});

it('never shows a negative saldo bisa digunakan even when saldo is below the floor', function () {
    app(SaldoFloorService::class)->simpan(50000);
    [$wali, $santri] = buatWaliDenganSantriBersaldo(20000);

    Livewire::actingAs($wali)->test(WaliSaldo::class)
        ->assertViewHas('saldoBisaDigunakan', 0);
});

it('omits the batas minimum note on the saldo page when the admin sets the floor to 0', function () {
    app(SaldoFloorService::class)->simpan(0);
    [$wali, $santri] = buatWaliDenganSantriBersaldo(100000);

    Livewire::actingAs($wali)->test(WaliSaldo::class)
        ->assertDontSee('batas minimum saldo');
});

it('shows saldo bisa digunakan on the bayar tagihan page', function () {
    app(SaldoFloorService::class)->simpan(20000);
    [$wali, $santri] = buatWaliDenganSantriBersaldo(100000);

    $jenis = JenisTagihan::factory()->create(['nominal_default' => 50000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::where('santri_id', $santri->id)->firstOrFail();

    Livewire::actingAs($wali)->test(Bayar::class, ['tagihan' => $tagihan])
        ->assertViewHas('saldoBisaDigunakan', 80000)
        ->assertSee('Saldo bisa digunakan')
        ->assertSee('80.000');
});
