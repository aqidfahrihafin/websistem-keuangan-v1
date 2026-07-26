<?php

use App\Livewire\Admin\Santri\Index as SantriIndex;
use App\Livewire\Admin\Santri\Show as SantriShow;
use App\Models\JenisTagihan;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Services\TagihanService;
use App\Services\WalletService;
use Livewire\Livewire;

it('soft-deletes a santri with zero saldo from the index list', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create();

    Livewire::actingAs($admin)->test(SantriIndex::class)
        ->call('hapus', $santri->id)
        ->assertSet('errorHapus', null);

    expect(Santri::find($santri->id))->toBeNull()
        ->and(Santri::withTrashed()->find($santri->id))->not->toBeNull();
});

it('refuses to delete a santri that still has a positive saldo, from the index list', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 50000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);

    Livewire::actingAs($admin)->test(SantriIndex::class)
        ->call('hapus', $santri->id)
        ->assertSet('errorHapus', fn (?string $pesan) => str_contains($pesan, 'saldo'));

    expect(Santri::find($santri->id))->not->toBeNull();
});

it('soft-deletes a santri with zero saldo from the detail page and redirects to the index', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create();

    Livewire::actingAs($admin)->test(SantriShow::class, ['santri' => $santri])
        ->call('hapus')
        ->assertRedirect(route('admin.santri.index'));

    expect(Santri::find($santri->id))->toBeNull();
});

it('refuses to delete a santri with a positive saldo from the detail page', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 25000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);

    Livewire::actingAs($admin)->test(SantriShow::class, ['santri' => $santri])
        ->call('hapus')
        ->assertNoRedirect()
        ->assertSet('errorHapus', fn (?string $pesan) => str_contains($pesan, 'saldo'));

    expect(Santri::find($santri->id))->not->toBeNull();
});

it('refuses to delete a santri that still has an unpaid tagihan', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 75000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);

    Livewire::actingAs($admin)->test(SantriIndex::class)
        ->call('hapus', $santri->id)
        ->assertSet('errorHapus', fn (?string $pesan) => str_contains($pesan, 'tagihan belum lunas'));

    expect(Santri::find($santri->id))->not->toBeNull();
});

it('excludes soft-deleted santri from the index listing', function () {
    $admin = makeUserWithRole('admin');
    $tetap = Santri::factory()->create(['nama' => 'Santri Tetap']);
    $terhapus = Santri::factory()->create(['nama' => 'Santri Terhapus']);
    $terhapus->delete();

    Livewire::actingAs($admin)->test(SantriIndex::class)
        ->assertSee('Santri Tetap')
        ->assertDontSee('Santri Terhapus');
});

it('forbids bendahara and wali from accessing the santri list - kesantrian record-keeping is admin-only', function () {
    $bendahara = makeUserWithRole('bendahara');
    $this->actingAs($bendahara)->get(route('admin.santri.index'))->assertForbidden();

    $wali = makeUserWithRole('wali');
    $this->actingAs($wali)->get(route('admin.santri.index'))->assertForbidden();
});
