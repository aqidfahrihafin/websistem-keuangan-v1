<?php

use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Laporan\Keuangan;
use App\Livewire\Admin\Laporan\LegerKasPondok;
use App\Livewire\Admin\Santri\Show as SantriShow;
use App\Livewire\Admin\Wali\Index as WaliIndex;
use App\Models\JenisTagihan;
use App\Models\Lembaga;
use App\Models\Periode;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Models\WaliSantri;
use App\Services\TagihanService;
use App\Services\WalletService;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

afterEach(function () {
    Carbon::setTestNow();
});

it('filters the dashboard activity rows by santri and transaction type', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));
    $admin = makeUserWithRole('admin');
    $target = Santri::factory()->create(['nama' => 'Santri Sasaran', 'nis' => 'NIS-SASARAN']);
    $other = Santri::factory()->create(['nama' => 'Santri Lain', 'nis' => 'NIS-LAIN']);
    $wallet = app(WalletService::class);

    $wallet->credit($target, 50000, Transaksi::JENIS_TOPUP_TUNAI);
    $wallet->credit($other, 25000, Transaksi::JENIS_PENYESUAIAN);

    Livewire::actingAs($admin)->test(Dashboard::class)
        ->set('aktivitasSearch', 'NIS-SASARAN')
        ->assertViewHas('aktivitas_terbaru', fn ($rows) => $rows->count() === 1
            && $rows->first()->santri_id === $target->id)
        ->set('aktivitasSearch', 'Penyesuaian')
        ->assertViewHas('aktivitas_terbaru', fn ($rows) => $rows->count() === 1
            && $rows->first()->santri_id === $other->id);
});

it('filters both financial report tables without changing the full report totals', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));
    $admin = makeUserWithRole('admin');
    Periode::factory()->create([
        'label' => '2026-07',
        'is_active' => true,
        'tanggal_mulai' => '2026-07-01',
        'tanggal_selesai' => '2026-07-31',
    ]);
    $santri = Santri::factory()->create();
    $wallet = app(WalletService::class);
    $wallet->credit($santri, 50000, Transaksi::JENIS_TOPUP_TUNAI);
    $wallet->credit($santri, 25000, Transaksi::JENIS_PENYESUAIAN);

    $makan = JenisTagihan::factory()->create(['nama' => 'Uang Makan', 'nominal_default' => 100000]);
    $spp = JenisTagihan::factory()->create(['nama' => 'SPP', 'nominal_default' => 75000]);
    app(TagihanService::class)->generateTagihanForPeriode($makan, '2026-07', null, null, null, [$santri->id]);
    app(TagihanService::class)->generateTagihanForPeriode($spp, '2026-07', null, null, null, [$santri->id]);

    Livewire::actingAs($admin)->test(Keuangan::class)
        ->set('transaksiSearch', 'Penyesuaian')
        ->assertViewHas('transaksiRows', fn ($rows) => $rows->count() === 1
            && $rows->first()['jenis'] === Transaksi::JENIS_PENYESUAIAN)
        ->assertViewHas('laporan', fn ($laporan) => count($laporan['transaksi']['per_jenis']) === 2)
        ->set('tagihanSearch', 'Makan')
        ->assertViewHas('tagihanRows', fn ($rows) => $rows->count() === 1
            && $rows->first()['nama'] === 'Uang Makan')
        ->assertViewHas('laporan', fn ($laporan) => count($laporan['tagihan']['per_jenis']) === 2);
});

it('filters both independent history tables on the santri detail page', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create();
    $makan = JenisTagihan::factory()->create(['nama' => 'Uang Makan', 'nominal_default' => 100000]);
    $spp = JenisTagihan::factory()->create(['nama' => 'SPP', 'nominal_default' => 75000]);
    app(TagihanService::class)->generateTagihanForPeriode($makan, '2026-07', null, null, null, [$santri->id]);
    app(TagihanService::class)->generateTagihanForPeriode($spp, '2026-07', null, null, null, [$santri->id]);

    $wallet = app(WalletService::class);
    $wallet->credit($santri, 50000, Transaksi::JENIS_TOPUP_TUNAI);
    $wallet->credit($santri, 25000, Transaksi::JENIS_PENYESUAIAN);

    Livewire::actingAs($admin)->test(SantriShow::class, ['santri' => $santri])
        ->set('tagihanSearch', 'Makan')
        ->assertViewHas('tagihans', fn ($rows) => $rows->total() === 1
            && $rows->first()->jenis_tagihan_id === $makan->id)
        ->set('transaksiSearch', 'Penyesuaian')
        ->assertViewHas('transaksis', fn ($rows) => $rows->total() === 1
            && $rows->first()->jenis === Transaksi::JENIS_PENYESUAIAN);
});

it('filters leger rows by the related santri name', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));
    $admin = makeUserWithRole('admin');
    $target = Santri::factory()->create(['nama' => 'Santri Kas Sasaran']);
    $other = Santri::factory()->create(['nama' => 'Santri Kas Lain']);
    $wallet = app(WalletService::class);
    $wallet->credit($target, 50000, Transaksi::JENIS_TOPUP_TUNAI);
    $wallet->credit($other, 25000, Transaksi::JENIS_TOPUP_TUNAI);

    Livewire::actingAs($admin)->test(LegerKasPondok::class)
        ->set('tanggal_dari', '2026-07-01')
        ->set('tanggal_sampai', '2026-07-31')
        ->set('search', 'Kas Sasaran')
        ->assertViewHas('entriPaginated', fn ($rows) => $rows->total() === 1
            && str_contains($rows->first()['pihak'], 'Santri Kas Sasaran'));
});

it('filters the linked wali table by a santri lembaga', function () {
    $admin = makeUserWithRole('admin');
    $targetWali = makeUserWithRole('wali', ['name' => 'Wali Sasaran']);
    $otherWali = makeUserWithRole('wali', ['name' => 'Wali Lain']);
    $targetLembaga = Lembaga::factory()->create(['nama' => 'Lembaga Sasaran']);
    $otherLembaga = Lembaga::factory()->create(['nama' => 'Lembaga Lain']);
    $targetSantri = Santri::factory()->create(['lembaga_id' => $targetLembaga->id]);
    $otherSantri = Santri::factory()->create(['lembaga_id' => $otherLembaga->id]);

    WaliSantri::create([
        'user_id' => $targetWali->id,
        'santri_id' => $targetSantri->id,
        'hubungan' => 'ayah',
        'is_auto_generated' => false,
        'is_primary' => true,
    ]);
    WaliSantri::create([
        'user_id' => $otherWali->id,
        'santri_id' => $otherSantri->id,
        'hubungan' => 'ayah',
        'is_auto_generated' => false,
        'is_primary' => true,
    ]);

    Livewire::actingAs($admin)->test(WaliIndex::class)
        ->set('listSearch', 'Lembaga Sasaran')
        ->assertViewHas('waliList', fn ($rows) => $rows->total() === 1
            && $rows->first()->id === $targetWali->id);
});
