<?php

use App\Livewire\Admin\Santri\Form as SantriForm;
use App\Models\JenisTagihan;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Services\TagihanService;
use App\Services\WalletService;
use Livewire\Livewire;

it('refuses to move a santri to nonaktif while they still have a positive saldo', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create(['status' => 'aktif']);
    app(WalletService::class)->credit($santri, 50000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);

    Livewire::actingAs($admin)->test(SantriForm::class, ['santri' => $santri])
        ->set('status', 'nonaktif')
        ->call('save')
        ->assertHasErrors(['status' => fn ($_, array $pesan) => str_contains($pesan[0] ?? '', 'saldo')]);

    expect($santri->fresh()->status)->toBe('aktif');
});

it('refuses to move a santri to lulus while they still have an unpaid tagihan', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create(['status' => 'aktif']);
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);

    Livewire::actingAs($admin)->test(SantriForm::class, ['santri' => $santri])
        ->set('status', 'lulus')
        ->call('save')
        ->assertHasErrors(['status' => fn ($_, array $pesan) => str_contains($pesan[0] ?? '', 'tagihan belum lunas')]);

    expect($santri->fresh()->status)->toBe('aktif');
});

it('allows moving a santri to keluar once saldo is zero and there is no outstanding tagihan', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create(['status' => 'aktif']);

    Livewire::actingAs($admin)->test(SantriForm::class, ['santri' => $santri])
        ->set('status', 'keluar')
        ->call('save')
        ->assertHasNoErrors();

    expect($santri->fresh()->status)->toBe('keluar');
});

it('does not block saving unrelated fields for a santri with saldo when status stays aktif', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create(['status' => 'aktif']);
    app(WalletService::class)->credit($santri, 50000, Transaksi::JENIS_TOPUP_TUNAI, ['metode' => Transaksi::METODE_TUNAI]);

    Livewire::actingAs($admin)->test(SantriForm::class, ['santri' => $santri])
        ->set('status', 'aktif')
        ->set('alamat', 'Alamat Baru')
        ->call('save')
        ->assertHasNoErrors();

    expect($santri->fresh()->alamat)->toBe('Alamat Baru');
});
