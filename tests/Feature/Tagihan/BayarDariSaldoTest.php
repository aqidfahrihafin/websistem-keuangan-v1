<?php

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\SaldoDiBawahMinimumException;
use App\Models\JenisTagihan;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\TagihanPembayaran;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\SaldoFloorService;
use App\Services\TagihanService;
use App\Services\WalletService;

function buatTagihanUntuk(Santri $santri, int $nominal, bool $bisaDicicil = false): Tagihan
{
    $jenis = JenisTagihan::factory()->create(['nominal_default' => $nominal, 'bisa_dicicil' => $bisaDicicil]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);

    return $santri->tagihans()->first();
}

it('pays a tagihan from saldo when the result stays at or above the minimum floor', function () {
    app(SaldoFloorService::class)->simpan(100000);

    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 250000, Transaksi::JENIS_TOPUP_TUNAI);
    $tagihan = buatTagihanUntuk($santri, 100000);
    $petugas = User::factory()->create();

    $pembayaran = app(TagihanService::class)->bayarDariSaldo($tagihan, $petugas);

    expect($pembayaran->sumber)->toBe(TagihanPembayaran::SUMBER_SALDO)
        ->and($tagihan->fresh()->status)->toBe(Tagihan::STATUS_LUNAS)
        ->and($santri->saldo->fresh()->saldo)->toBe(150000);
});

it('refuses to pay from saldo when doing so would drop saldo below the minimum floor, without mutating anything', function () {
    app(SaldoFloorService::class)->simpan(100000);

    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 150000, Transaksi::JENIS_TOPUP_TUNAI);
    $tagihan = buatTagihanUntuk($santri, 100000);
    $petugas = User::factory()->create();

    expect(fn () => app(TagihanService::class)->bayarDariSaldo($tagihan, $petugas))
        ->toThrow(SaldoDiBawahMinimumException::class, 'Midtrans');

    expect($tagihan->fresh()->status)->toBe(Tagihan::STATUS_BELUM_LUNAS)
        ->and($santri->saldo->fresh()->saldo)->toBe(150000)
        ->and(TagihanPembayaran::where('tagihan_id', $tagihan->id)->count())->toBe(0);
});

it('still throws InsufficientBalanceException, distinct from the floor exception, when saldo is truly short', function () {
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 20000, Transaksi::JENIS_TOPUP_TUNAI);
    $tagihan = buatTagihanUntuk($santri, 100000);
    $petugas = User::factory()->create();

    expect(fn () => app(TagihanService::class)->bayarDariSaldo($tagihan, $petugas))
        ->toThrow(InsufficientBalanceException::class);
});

it('accepts a partial nominal from saldo when the jenis tagihan allows cicilan, leaving the tagihan sebagian', function () {
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);
    $tagihan = buatTagihanUntuk($santri, 100000, bisaDicicil: true);
    $petugas = User::factory()->create();

    $pembayaran = app(TagihanService::class)->bayarDariSaldo($tagihan, $petugas, 40000);

    expect($pembayaran->nominal)->toBe(40000)
        ->and($tagihan->fresh()->status)->toBe(Tagihan::STATUS_SEBAGIAN)
        ->and($tagihan->fresh()->nominal_terbayar)->toBe(40000)
        ->and($santri->saldo->fresh()->saldo)->toBe(160000);
});

it('lets a cicilan tagihan be paid off across multiple separate saldo payments', function () {
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);
    $tagihan = buatTagihanUntuk($santri, 100000, bisaDicicil: true);
    $petugas = User::factory()->create();
    $service = app(TagihanService::class);

    $service->bayarDariSaldo($tagihan, $petugas, 30000);
    $service->bayarDariSaldo($tagihan, $petugas, 30000);
    $service->bayarDariSaldo($tagihan, $petugas, 40000);

    expect($tagihan->fresh()->status)->toBe(Tagihan::STATUS_LUNAS)
        ->and($tagihan->fresh()->nominal_terbayar)->toBe(100000)
        ->and(TagihanPembayaran::where('tagihan_id', $tagihan->id)->count())->toBe(3);
});

it('rejects a partial saldo payment for a jenis tagihan that does not allow cicilan', function () {
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);
    $tagihan = buatTagihanUntuk($santri, 100000, bisaDicicil: false);
    $petugas = User::factory()->create();

    expect(fn () => app(TagihanService::class)->bayarDariSaldo($tagihan, $petugas, 40000))
        ->toThrow(InvalidArgumentException::class, 'tidak bisa dicicil');

    expect($tagihan->fresh()->status)->toBe(Tagihan::STATUS_BELUM_LUNAS)
        ->and($santri->saldo->fresh()->saldo)->toBe(200000);
});

it('rejects a nominal larger than the remaining sisa even on a cicilan-enabled jenis', function () {
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);
    $tagihan = buatTagihanUntuk($santri, 100000, bisaDicicil: true);
    $petugas = User::factory()->create();

    expect(fn () => app(TagihanService::class)->bayarDariSaldo($tagihan, $petugas, 150000))
        ->toThrow(InvalidArgumentException::class, 'melebihi sisa');
});
