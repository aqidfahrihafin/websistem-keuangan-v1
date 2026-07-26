<?php

use App\Livewire\Admin\Santri\Show as SantriShow;
use App\Models\JenisTagihan;
use App\Models\KartuSantri;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Models\WaliSantri;
use App\Services\TagihanService;
use App\Services\WalletService;
use Livewire\Livewire;

it('renders the santri detail page with biodata, saldo, kartu, wali, tagihan, and transaksi', function () {
    $admin = makeUserWithRole('admin');
    $wali = makeUserWithRole('wali');

    $santri = Santri::factory()->create([
        'nama' => 'Ahmad Fauzi',
        'jenis_kelamin' => 'L',
    ]);

    WaliSantri::create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'hubungan' => 'ayah',
        'is_auto_generated' => false,
        'is_primary' => true,
    ]);

    KartuSantri::factory()->create(['santri_id' => $santri->id, 'status' => 'aktif']);

    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);

    app(WalletService::class)->credit($santri, 50000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);

    $this->actingAs($admin)->get(route('admin.santri.show', $santri))
        ->assertOk()
        ->assertSee('Ahmad Fauzi')
        ->assertSee($santri->nis)
        ->assertSee('Rp 50.000')
        ->assertSee($wali->name)
        ->assertSee('Top Up Tunai');
});

it('paginates tagihan and transaksi independently, 10 rows per page, showing the full history not a capped preview', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create();

    $jenis = JenisTagihan::factory()->create(['nominal_default' => 10000]);
    foreach (range(1, 12) as $i) {
        app(TagihanService::class)->generateTagihanForPeriode($jenis, sprintf('2025-%02d', $i), null, null, null, [$santri->id]);
    }
    foreach (range(1, 12) as $i) {
        app(WalletService::class)->credit($santri, 1000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);
    }

    $component = Livewire::actingAs($admin)->test(SantriShow::class, ['santri' => $santri]);

    $component->assertViewHas('tagihans', fn ($p) => $p->count() === 10 && $p->total() === 12)
        ->assertViewHas('transaksis', fn ($p) => $p->count() === 10 && $p->total() === 12);

    // Paging through tagihan must not disturb the transaksi paginator's
    // own position - they're independent paginators on the same page.
    $component->call('gotoPage', 2, 'tagihanPage')
        ->assertViewHas('tagihans', fn ($p) => $p->count() === 2 && $p->currentPage() === 2)
        ->assertViewHas('transaksis', fn ($p) => $p->currentPage() === 1);
});
