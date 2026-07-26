<?php

use App\Livewire\Admin\Laporan\Keuangan;
use App\Livewire\Admin\Laporan\Keuangan as LaporanKeuangan;
use App\Models\JenisTagihan;
use App\Models\KategoriDiskon;
use App\Models\Lembaga;
use App\Models\Periode;
use App\Models\Santri;
use App\Models\TagihanPembayaran;
use App\Models\Transaksi;
use App\Services\LaporanKeuanganService;
use App\Services\TagihanService;
use App\Services\WalletService;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('aggregates transaksi, tagihan, and saldo correctly within the given date range', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15'));

    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    $wallet = app(WalletService::class);
    $tagihanService = app(TagihanService::class);

    // In range: a topup credit of 200,000 (real cash in) and a debit
    // (pembayaran tagihan dari saldo) of 50,000 - the latter deliberately
    // does NOT move total_debit/net, since paying a tagihan from saldo
    // already-received doesn't take any new cash out of the pondok (see
    // ringkasanTransaksi()'s JENIS_KAS_KELUAR).
    $wallet->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);
    $wallet->debit($santri, 50000, Transaksi::JENIS_PEMBAYARAN_TAGIHAN, ['metode' => Transaksi::METODE_SISTEM]);

    // A tagihan generated in range, partially paid.
    $tagihanService->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = $santri->tagihans()->first();
    $tagihanService->applyPembayaran($tagihan, 30000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);

    // Out of range: should not be counted.
    Carbon::setTestNow(Carbon::parse('2026-05-01'));
    $wallet->credit($santri, 999999, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);
    Carbon::setTestNow(Carbon::parse('2026-07-15'));

    $laporan = app(LaporanKeuanganService::class)->generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

    expect($laporan['transaksi']['total_kredit'])->toBe(200000)
        ->and($laporan['transaksi']['total_debit'])->toBe(0)
        ->and($laporan['transaksi']['net'])->toBe(200000)
        ->and($laporan['tagihan']['total_nominal'])->toBe(100000)
        ->and($laporan['tagihan']['total_terbayar'])->toBe(30000)
        ->and($laporan['tagihan']['total_sisa'])->toBe(70000)
        // No kategori diskon applied on this santri/jenis, so "sebelum diskon" equals the nominal itself and diskon is 0.
        ->and($laporan['tagihan']['total_sebelum_diskon'])->toBe(100000)
        ->and($laporan['tagihan']['total_diskon'])->toBe(0)
        ->and($laporan['saldo_santri_saat_ini'])->toBe(200000 - 50000 + 999999);

    Carbon::setTestNow();
});

it('counts distinct santri who have made at least one payment per jenis tagihan', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15'));

    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    $santriA = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    $santriB = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    $tagihanService = app(TagihanService::class);

    $tagihanService->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santriA->id, $santriB->id]);

    $tagihanA = $santriA->tagihans()->first();
    $tagihanService->applyPembayaran($tagihanA, 30000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);
    // $santriB's tagihan is left unpaid on purpose.

    $laporan = app(LaporanKeuanganService::class)->generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

    expect($laporan['tagihan']['per_jenis'][0]['santri_bayar'])->toBe(1)
        ->and($laporan['tagihan']['total_santri_bayar'])->toBe(1);

    Carbon::setTestNow();
});

it('reports the discount given separately from the discounted (final) tagihan nominal', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15'));

    $kategori = KategoriDiskon::factory()->create(['persentase' => 20, 'is_active' => true]);
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF, 'kategori_diskon_id' => $kategori->id]);
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000, 'berlaku_diskon' => true]);

    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);

    $laporan = app(LaporanKeuanganService::class)->generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));

    expect($laporan['tagihan']['total_sebelum_diskon'])->toBe(100000)
        ->and($laporan['tagihan']['total_diskon'])->toBe(20000)
        ->and($laporan['tagihan']['total_nominal'])->toBe(80000)
        ->and($laporan['tagihan']['per_jenis'][0]['sebelum_diskon'])->toBe(100000)
        ->and($laporan['tagihan']['per_jenis'][0]['diskon'])->toBe(20000)
        ->and($laporan['tagihan']['per_jenis'][0]['nominal'])->toBe(80000);

    Carbon::setTestNow();
});

it('filters the report by lembaga', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15'));

    $lembagaA = Lembaga::factory()->create();
    $lembagaB = Lembaga::factory()->create();
    $santriA = Santri::factory()->create(['lembaga_id' => $lembagaA->id]);
    $santriB = Santri::factory()->create(['lembaga_id' => $lembagaB->id]);
    $wallet = app(WalletService::class);

    $wallet->credit($santriA, 100000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);
    $wallet->credit($santriB, 500000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);

    $laporan = app(LaporanKeuanganService::class)->generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'), $lembagaA->id);

    expect($laporan['transaksi']['total_kredit'])->toBe(100000);

    Carbon::setTestNow();
});

it('defaults to the periode aktif date range and switches when a different periode is picked', function () {
    $admin = makeUserWithRole('admin');
    $juni = Periode::factory()->create(['label' => '2026-06', 'is_active' => false, 'tanggal_mulai' => '2026-06-01', 'tanggal_selesai' => '2026-06-30']);
    $aktif = Periode::factory()->create(['label' => '2026-07', 'is_active' => true, 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2026-07-31']);

    Livewire::actingAs($admin)->test(LaporanKeuangan::class)
        ->assertSet('periode_pilihan', '2026-07')
        ->assertSet('tanggal_dari', '2026-07-01')
        ->assertSet('tanggal_sampai', '2026-07-31')
        ->set('periode_pilihan', '2026-06')
        ->assertSet('tanggal_dari', $juni->tanggal_mulai->toDateString())
        ->assertSet('tanggal_sampai', $juni->tanggal_selesai->toDateString());
});

it('reveals editable date inputs only when kustom is selected', function () {
    $admin = makeUserWithRole('admin');
    Periode::factory()->create(['label' => '2026-07', 'is_active' => true, 'tanggal_mulai' => '2026-07-01', 'tanggal_selesai' => '2026-07-31']);

    Livewire::actingAs($admin)->test(LaporanKeuangan::class)
        ->assertViewHas('isKustom', false)
        ->set('periode_pilihan', Keuangan::KUSTOM)
        ->assertViewHas('isKustom', true)
        ->set('tanggal_dari', '2026-01-01')
        ->set('tanggal_sampai', '2026-01-31')
        ->assertSet('tanggal_dari', '2026-01-01')
        ->assertSet('tanggal_sampai', '2026-01-31');
});

it('downloads the laporan keuangan as excel and pdf for an admin, but forbids other roles', function () {
    $admin = makeUserWithRole('admin');
    $santri = makeUserWithRole('santri');

    $this->actingAs($admin)->get(route('admin.laporan-keuangan.export.excel'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($admin)->get(route('admin.laporan-keuangan.export.pdf'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($santri)->get(route('admin.laporan-keuangan.export.excel'))->assertForbidden();
});
