<?php

use App\Livewire\Admin\Laporan\LegerKasPondok;
use App\Models\JenisTagihan;
use App\Models\KartuSantri;
use App\Models\KebijakanPenarikan;
use App\Models\Lembaga;
use App\Models\Santri;
use App\Models\TagihanPembayaran;
use App\Models\TopupWali;
use App\Models\Transaksi;
use App\Models\UnitUsaha;
use App\Models\UnitUsahaPenarikan;
use App\Services\LegerKasPondokService;
use App\Services\PenarikanService;
use App\Services\TagihanService;
use App\Services\UnitUsahaPenarikanService;
use App\Services\WalletService;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

function withKebijakanPenarikan(): KebijakanPenarikan
{
    return KebijakanPenarikan::factory()->create([
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '20:00:00',
        'limit_harian' => 1000000,
        'is_active' => true,
        'effective_from' => '2020-01-01',
    ]);
}

afterEach(function () {
    Carbon::setTestNow();
});

it('includes cash-in from topup_tunai, topup wali (midtrans), and cash-paid tagihan, plus cash-out from penarikan_tunai', function () {
    withKebijakanPenarikan();
    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));

    $santri = Santri::factory()->create();
    $wallet = app(WalletService::class);
    $tagihanService = app(TagihanService::class);
    $penarikanService = app(PenarikanService::class);

    // Kas masuk: 100.000 tunai di kasir.
    $wallet->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);

    // Kas masuk: 50.000 tagihan dibayar tunai langsung (tidak lewat wallet).
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 50000]);
    $tagihanService->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = $santri->tagihans()->first();
    $tagihanService->applyPembayaran($tagihan, 50000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);

    // Kas keluar: 30.000 penarikan tunai (dari saldo yang sudah di-topup).
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'fingerprint_template_ref' => 'FP-LEGER-1']);
    $pengurus = makeUserWithRole('admin');
    $request = $penarikanService->createRequest($santri, 30000);
    $penarikanService->fulfill($penarikanService->approve($request, $pengurus), $pengurus, 'FP-LEGER-1');

    // TIDAK dihitung: bayar tagihan dari saldo (uangnya sudah masuk saat topup).
    $wallet->debit($santri, 20000, Transaksi::JENIS_PEMBAYARAN_TAGIHAN, ['metode' => Transaksi::METODE_SISTEM]);

    // TIDAK dihitung: penyesuaian.
    $wallet->credit($santri, 5000, Transaksi::JENIS_PENYESUAIAN, ['metode' => Transaksi::METODE_SISTEM]);

    $leger = app(LegerKasPondokService::class)->generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

    expect($leger['total_masuk'])->toBe(150000)
        ->and($leger['total_keluar'])->toBe(30000)
        ->and($leger['saldo_akhir'])->toBe(120000)
        ->and($leger['total_masuk_tunai'])->toBe(150000)
        ->and($leger['total_masuk_midtrans'])->toBe(0)
        ->and($leger['entri'])->toHaveCount(3);
});

it('counts a paid wali topup as midtrans cash-in for the full nominal_diminta, even when part of it paid off a tagihan', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));

    $santri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = $santri->tagihans()->first();

    $wali = makeUserWithRole('wali');
    TopupWali::factory()->create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'nominal_diminta' => 150000,
        'nominal_potongan_tagihan' => 100000,
        'nominal_ke_saldo' => 50000,
        'status' => TopupWali::STATUS_PAID,
        'paid_at' => now(),
    ]);

    $leger = app(LegerKasPondokService::class)->generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

    expect($leger['total_masuk'])->toBe(150000)
        ->and($leger['total_masuk_midtrans'])->toBe(150000)
        ->and($leger['entri'][0]['sumber_dana'])->toBe('midtrans');
});

it('counts a tagihan-scoped wali topup as midtrans cash-in for its full nominal_diminta', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));

    $santri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 90000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = $santri->tagihans()->first();

    $wali = makeUserWithRole('wali');
    TopupWali::factory()->create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'tagihan_id' => $tagihan->id,
        'nominal_diminta' => 90000,
        'nominal_potongan_tagihan' => 90000,
        'nominal_ke_saldo' => 0,
        'status' => TopupWali::STATUS_PAID,
        'paid_at' => now(),
    ]);

    $leger = app(LegerKasPondokService::class)->generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

    expect($leger['total_masuk'])->toBe(90000)
        ->and($leger['total_masuk_midtrans'])->toBe(90000)
        ->and($leger['entri'][0]['sumber_dana'])->toBe('midtrans');
});

it('records a pondok-borne Midtrans fee as a separate cash-out and reduces uang_milik_pondok', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));

    $santri = Santri::factory()->create();
    $wali = makeUserWithRole('wali');
    TopupWali::factory()->create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'nominal_diminta' => 100000,
        'nominal_ke_saldo' => 100000,
        'biaya_midtrans' => 4000,
        'biaya_ditanggung_wali' => false,
        'status' => TopupWali::STATUS_PAID,
        'paid_at' => now(),
    ]);
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TRANSFER_WALI, ['metode' => Transaksi::METODE_MIDTRANS]);

    $leger = app(LegerKasPondokService::class)->generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

    expect($leger['entri'])->toHaveCount(2)
        ->and($leger['total_masuk'])->toBe(100000)
        ->and($leger['total_keluar'])->toBe(4000)
        ->and($leger['uang_milik_pondok'])->toBe(100000 - 4000 - 100000)
        ->and(collect($leger['entri'])->firstWhere('jenis', 'Biaya Admin Midtrans'))
        ->toMatchArray(['pihak' => 'Midtrans', 'sumber_dana' => 'midtrans', 'masuk' => 0, 'keluar' => 4000]);
});

it('does not add a fee expense entry when the Midtrans fee is wali-borne', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));

    $santri = Santri::factory()->create();
    $wali = makeUserWithRole('wali');
    TopupWali::factory()->create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'nominal_diminta' => 100000,
        'nominal_ke_saldo' => 100000,
        'biaya_midtrans' => 4000,
        'biaya_ditanggung_wali' => true,
        'status' => TopupWali::STATUS_PAID,
        'paid_at' => now(),
    ]);

    $leger = app(LegerKasPondokService::class)->generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

    expect($leger['entri'])->toHaveCount(1)
        ->and($leger['total_keluar'])->toBe(0);
});

it('carries forward saldo_awal from entries before the filtered date range', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-10 10:00:00'));
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 75000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);

    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));
    app(WalletService::class)->credit($santri, 25000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);

    $leger = app(LegerKasPondokService::class)->generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

    expect($leger['saldo_awal'])->toBe(75000)
        ->and($leger['total_masuk'])->toBe(25000)
        ->and($leger['saldo_akhir'])->toBe(100000);
});

it('filters entries by sumber_dana', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 60000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);

    $wali = makeUserWithRole('wali');
    TopupWali::factory()->create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'nominal_diminta' => 40000,
        'status' => TopupWali::STATUS_PAID,
        'paid_at' => now(),
    ]);

    $legerTunai = app(LegerKasPondokService::class)->generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'), null, 'tunai');
    $legerMidtrans = app(LegerKasPondokService::class)->generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'), null, 'midtrans');

    expect($legerTunai['entri'])->toHaveCount(1)
        ->and($legerTunai['total_masuk'])->toBe(60000)
        ->and($legerMidtrans['entri'])->toHaveCount(1)
        ->and($legerMidtrans['total_masuk'])->toBe(40000);
});

it('filters entries by lembaga', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));

    $lembagaA = Lembaga::factory()->create();
    $lembagaB = Lembaga::factory()->create();
    $santriA = Santri::factory()->create(['lembaga_id' => $lembagaA->id]);
    $santriB = Santri::factory()->create(['lembaga_id' => $lembagaB->id]);

    app(WalletService::class)->credit($santriA, 30000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);
    app(WalletService::class)->credit($santriB, 70000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);

    $leger = app(LegerKasPondokService::class)->generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'), $lembagaA->id);

    expect($leger['total_masuk'])->toBe(30000)
        ->and($leger['entri'])->toHaveCount(1);
});

it('counts a fulfilled kantin pencairan as cash-out under sumber_dana transfer_bank', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));

    $admin = makeUserWithRole('admin');
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 100000]);
    $service = app(UnitUsahaPenarikanService::class);

    $request = $service->createRequest($unit, 40000, $admin);
    $request = $service->approve($request, $admin);
    $service->fulfill($request, $admin, 'TRX-BCA-LEGER-1');

    $leger = app(LegerKasPondokService::class)->generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

    expect($leger['total_keluar'])->toBe(40000)
        ->and($leger['total_keluar_transfer_bank'])->toBe(40000)
        ->and($leger['entri'])->toHaveCount(1)
        ->and($leger['entri'][0]['sumber_dana'])->toBe('transfer_bank')
        ->and($leger['entri'][0]['pihak'])->toBe($unit->nama);
});

it('shows a fulfilled cash kantin withdrawal in the cash ledger and cash filter', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));

    $admin = makeUserWithRole('admin');
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 100000]);
    $service = app(UnitUsahaPenarikanService::class);

    $request = $service->createRequest(
        $unit,
        40000,
        $admin,
        UnitUsahaPenarikan::METODE_TUNAI,
    );
    $request = $service->approve($request, $admin);
    $service->fulfill($request, $admin, null, $request->kode_serah_terima);

    $leger = app(LegerKasPondokService::class)->generate(
        Carbon::parse('2026-07-01'),
        Carbon::parse('2026-07-31'),
        null,
        LegerKasPondokService::SUMBER_TUNAI,
    );

    expect($leger['total_keluar'])->toBe(40000)
        ->and($leger['total_keluar_tunai'])->toBe(40000)
        ->and($leger['total_keluar_kantin_tunai'])->toBe(40000)
        ->and($leger['total_keluar_transfer_bank'])->toBe(0)
        ->and($leger['entri'])->toHaveCount(1)
        ->and($leger['entri'][0]['jenis'])->toBe('Pencairan Tunai Kantin')
        ->and($leger['entri'][0]['sumber_dana'])->toBe('tunai')
        ->and($leger['entri'][0]['pihak'])->toBe($unit->nama);
});

it('does not count a kantin penarikan request that is not yet selesai', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));

    $admin = makeUserWithRole('admin');
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 100000]);
    app(UnitUsahaPenarikanService::class)->createRequest($unit, 40000, $admin);

    $leger = app(LegerKasPondokService::class)->generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

    expect($leger['entri'])->toHaveCount(0)
        ->and($leger['total_keluar'])->toBe(0);
});

it('excludes kantin pencairan when filtering by lembaga, since it has none', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));

    $lembaga = Lembaga::factory()->create();
    $admin = makeUserWithRole('admin');
    $unit = UnitUsaha::factory()->create(['saldo_unit' => 100000]);
    $service = app(UnitUsahaPenarikanService::class);
    $service->fulfill($service->approve($service->createRequest($unit, 40000, $admin), $admin), $admin, 'TRX-BCA-LEGER-2');

    $leger = app(LegerKasPondokService::class)->generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'), $lembaga->id);

    expect($leger['entri'])->toHaveCount(0);
});

it('lets an admin view the Leger Kas Pondok page, but forbids other roles', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(LegerKasPondok::class)->assertOk();

    $wali = makeUserWithRole('wali');
    $this->actingAs($wali)->get(route('admin.leger-kas-pondok.index'))->assertForbidden();
});
