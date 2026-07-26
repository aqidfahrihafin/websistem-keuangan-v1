<?php

use App\Livewire\Admin\Transaksi\Index as TransaksiIndex;
use App\Livewire\Wali\Saldo as WaliSaldo;
use App\Models\JenisTagihan;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\TagihanPembayaran;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\WaliSantri;
use App\Services\TagihanService;
use App\Services\WalletService;
use Livewire\Livewire;

function buatTagihanCicilanTerbayarSebagian(): Tagihan
{
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);

    $jenis = JenisTagihan::factory()->create(['nama' => 'SPP Bulanan', 'nominal_default' => 100000, 'bisa_dicicil' => true]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::where('santri_id', $santri->id)->firstOrFail();

    app(TagihanService::class)->bayarDariSaldo($tagihan, User::factory()->create(), 40000);

    return $tagihan->fresh();
}

it('shows terbayar/sisa on the admin transaksi list for a cicilan payment still sebagian', function () {
    $tagihan = buatTagihanCicilanTerbayarSebagian();
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(TransaksiIndex::class)
        ->assertSee('Cicilan SPP Bulanan')
        ->assertSee('terbayar Rp 40.000')
        ->assertSee('sisa Rp 60.000');
});

it('does not show the cicilan note on the admin transaksi list once the tagihan is fully paid', function () {
    $tagihan = buatTagihanCicilanTerbayarSebagian();
    app(TagihanService::class)->bayarDariSaldo($tagihan, User::factory()->create(), 60000);
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(TransaksiIndex::class)
        ->assertDontSee('Cicilan SPP Bulanan');
});

it('shows terbayar/sisa on the wali saldo/riwayat page for a cicilan payment still sebagian', function () {
    $tagihan = buatTagihanCicilanTerbayarSebagian();
    $wali = makeUserWithRole('wali');
    WaliSantri::create(['user_id' => $wali->id, 'santri_id' => $tagihan->santri_id, 'hubungan' => 'ayah', 'is_auto_generated' => true, 'is_primary' => true]);

    Livewire::actingAs($wali)->test(WaliSaldo::class)
        ->assertSee('Cicilan SPP Bulanan')
        ->assertSee('terbayar Rp 40.000')
        ->assertSee('sisa Rp 60.000');
});

it('does not show a cicilan note for a transaksi unrelated to any tagihan', function () {
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(TransaksiIndex::class)
        ->assertDontSee('Cicilan');

    expect(TagihanPembayaran::count())->toBe(0);
});
