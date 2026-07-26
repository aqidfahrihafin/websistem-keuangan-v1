<?php

use App\Livewire\Admin\Dashboard;
use App\Models\JenisTagihan;
use App\Models\PenarikanRequest;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Services\DashboardService;
use App\Services\TagihanService;
use App\Services\WalletService;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

afterEach(function () {
    Carbon::setTestNow();
});

it('counts santri, saldo, tagihan, and penarikan stats correctly', function () {
    Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    Santri::factory()->create(['status' => Santri::STATUS_BARU]);

    $santri = Santri::where('status', Santri::STATUS_AKTIF)->first();
    app(WalletService::class)->credit($santri, 50000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);

    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);

    PenarikanRequest::create(['santri_id' => $santri->id, 'nominal_diminta' => 20000, 'status' => PenarikanRequest::STATUS_MENUNGGU]);
    PenarikanRequest::create(['santri_id' => $santri->id, 'nominal_diminta' => 15000, 'status' => PenarikanRequest::STATUS_DISETUJUI]);
    PenarikanRequest::create([
        'santri_id' => $santri->id, 'nominal_diminta' => 10000, 'status' => PenarikanRequest::STATUS_DISETUJUI,
        'wajib_surat_keterangan' => true, 'surat_keterangan_status' => PenarikanRequest::SURAT_MENUNGGU_REVIEW,
    ]);

    $ringkasan = app(DashboardService::class)->ringkasan();

    expect($ringkasan['santri_aktif'])->toBe(2)
        ->and($ringkasan['santri_baru'])->toBe(1)
        ->and($ringkasan['saldo_santri_total'])->toBe(50000)
        ->and($ringkasan['tagihan_belum_lunas'])->toBe(1)
        ->and($ringkasan['penarikan_menunggu'])->toBe(1)
        ->and($ringkasan['penarikan_disetujui'])->toBe(2)
        ->and($ringkasan['surat_menunggu_review'])->toBe(1);
});

it('fills the 30-day transaksi trend with zeroes on days without activity', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-30 12:00:00'));

    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);

    Carbon::setTestNow(Carbon::parse('2026-07-25 09:00:00'));
    app(WalletService::class)->credit($santri, 50000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);

    Carbon::setTestNow(Carbon::parse('2026-07-30 12:00:00'));

    $tren = collect(app(DashboardService::class)->ringkasan()['tren_transaksi'])->keyBy('tanggal');

    expect($tren)->toHaveCount(30)
        ->and($tren['2026-07-30']['total'])->toBe(100000)
        ->and($tren['2026-07-25']['total'])->toBe(50000)
        ->and($tren['2026-07-20']['total'])->toBe(0)
        ->and($tren['2026-07-20']['jumlah'])->toBe(0);
});

it('forward-fills the kas pondok running balance across days without cash movement', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-20 10:00:00'));
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 80000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);

    Carbon::setTestNow(Carbon::parse('2026-07-30 12:00:00'));

    $tren = collect(app(DashboardService::class)->ringkasan()['tren_kas_pondok'])->keyBy('tanggal');

    expect($tren['2026-07-01']['saldo'])->toBe(0)
        ->and($tren['2026-07-19']['saldo'])->toBe(0)
        ->and($tren['2026-07-20']['saldo'])->toBe(80000)
        ->and($tren['2026-07-25']['saldo'])->toBe(80000)
        ->and($tren['2026-07-30']['saldo'])->toBe(80000);
});

it('breaks tagihan and santri counts down by status, including zero for statuses with no rows', function () {
    Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    Santri::factory()->create(['status' => Santri::STATUS_LULUS]);

    $ringkasan = app(DashboardService::class)->ringkasan();

    expect($ringkasan['status_santri'])->toBe([
        'aktif' => 1, 'baru' => 0, 'nonaktif' => 0, 'lulus' => 1, 'keluar' => 0,
    ])->and($ringkasan['status_tagihan'])->toBe([
        'belum_lunas' => 0, 'sebagian' => 0, 'lunas' => 0, 'dibatalkan' => 0,
    ]);
});

it('returns only the 8 most recent transaksi as recent activity', function () {
    $santri = Santri::factory()->create();
    $wallet = app(WalletService::class);

    Carbon::setTestNow(Carbon::parse('2026-07-01 08:00:00'));
    for ($i = 0; $i < 10; $i++) {
        Carbon::setTestNow(Carbon::parse('2026-07-01 08:00:00')->addMinutes($i));
        $wallet->credit($santri, 1000 + $i, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);
    }

    $aktivitas = app(DashboardService::class)->ringkasan()['aktivitas_terbaru'];

    expect($aktivitas)->toHaveCount(8)
        ->and($aktivitas->first()->nominal)->toBe(1009)
        ->and($aktivitas->last()->nominal)->toBe(1002);
});

it('lets an admin view the dashboard, but forbids other roles', function () {
    $admin = makeUserWithRole('admin');

    Livewire::actingAs($admin)->test(Dashboard::class)->assertOk();

    $wali = makeUserWithRole('wali');
    $this->actingAs($wali)->get(route('admin.dashboard'))->assertForbidden();
});
