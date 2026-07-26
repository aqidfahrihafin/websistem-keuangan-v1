<?php

use App\Livewire\Admin\Laporan\LegerKasPondok;
use App\Livewire\Admin\Lembaga\Index as LembagaIndex;
use App\Livewire\Admin\Santri\Show as SantriShow;
use App\Livewire\Admin\Users\Index as UsersIndex;
use App\Models\Lembaga;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Services\WalletService;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('changes visible row count and resets to page 1 on an already-paginated page', function () {
    $admin = makeUserWithRole('admin');
    \App\Models\User::factory()->count(15)->create();

    $component = Livewire::actingAs($admin)->test(UsersIndex::class);

    // Default is 10 everywhere now, even though this page used to hardcode 15.
    $component->assertViewHas('users', fn ($users) => $users->count() === 10 && $users->perPage() === 10);

    $component->call('gotoPage', 2)
        ->assertViewHas('users', fn ($users) => $users->currentPage() === 2);

    $component->set('perPage', 25)
        ->assertViewHas('users', fn ($users) => $users->count() === 16 && $users->currentPage() === 1);
});

it('paginates a page that previously had no pagination at all', function () {
    $admin = makeUserWithRole('admin');
    Lembaga::factory()->count(12)->create();

    Livewire::actingAs($admin)->test(LembagaIndex::class)
        ->assertViewHas('lembagas', fn ($lembagas) => $lembagas->count() === 10 && $lembagas->total() === 12)
        ->set('perPage', 50)
        ->assertViewHas('lembagas', fn ($lembagas) => $lembagas->count() === 12);
});

it('keeps Admin Santri Show\'s two per-page selectors independent of each other', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create();

    foreach (range(1, 12) as $i) {
        app(WalletService::class)->credit($santri, 1000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);
    }

    $component = Livewire::actingAs($admin)->test(SantriShow::class, ['santri' => $santri]);

    $component->assertViewHas('transaksis', fn ($p) => $p->count() === 10);

    // Changing tagihanPerPage must not touch the transaksi paginator at all.
    $component->set('tagihanPerPage', 25)
        ->assertViewHas('transaksis', fn ($p) => $p->count() === 10 && $p->currentPage() === 1);

    $component->set('transaksiPerPage', 25)
        ->assertViewHas('transaksis', fn ($p) => $p->count() === 12 && $p->currentPage() === 1);
});

it('keeps the running balance and full-range totals correct on Leger Kas Pondok regardless of which page is displayed', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create();

    foreach (range(1, 12) as $i) {
        app(WalletService::class)->credit($santri, 10000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);
    }

    $component = Livewire::actingAs($admin)->test(LegerKasPondok::class)
        ->set('tanggal_dari', '2026-07-01')
        ->set('tanggal_sampai', '2026-07-31');

    $component->assertViewHas('entriPaginated', fn ($p) => $p->count() === 10 && $p->total() === 12)
        ->assertViewHas('leger', fn ($leger) => $leger['total_masuk'] === 120000 && $leger['saldo_akhir'] === 120000);

    // Page 2 must still reflect the full-range totals, not a recomputation
    // scoped to just the visible page.
    $component->call('gotoPage', 2)
        ->assertViewHas('entriPaginated', fn ($p) => $p->count() === 2 && $p->currentPage() === 2)
        ->assertViewHas('leger', fn ($leger) => $leger['total_masuk'] === 120000 && $leger['saldo_akhir'] === 120000);

    Carbon::setTestNow();
});
