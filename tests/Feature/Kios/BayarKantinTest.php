<?php

use App\Livewire\Kios\BayarKantin;
use App\Models\Device;
use App\Models\KartuSantri;
use App\Models\KebijakanKantin;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Models\UnitUsaha;
use App\Services\KantinPembayaranService;
use App\Services\WalletService;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(fn () => Cache::flush());

function perangkatKantin(array $attributes = []): Device
{
    $unit = UnitUsaha::factory()->create(['status' => UnitUsaha::STATUS_AKTIF]);

    return Device::factory()->create(array_merge([
        'tipe' => Device::TIPE_KANTIN,
        'status' => 'aktif',
        'unit_usaha_id' => $unit->id,
    ], $attributes));
}

it('opens only for an active kantin device linked to an active unit usaha', function () {
    $device = perangkatKantin();

    $this->get("/kios-kantin/{$device->kode_device}")
        ->assertOk()
        ->assertSee($device->unitUsaha->nama);

    $inactive = perangkatKantin(['status' => 'nonaktif']);
    $this->get("/kios-kantin/{$inactive->kode_device}")->assertNotFound();
});

it('completes a card and fingerprint authorized kantin payment', function () {
    $device = perangkatKantin();
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    // Sisakan saldo di atas batas minimum default Rp 100.000 setelah
    // transaksi agar skenario ini benar-benar menguji alur kartu + sidik jari.
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create([
        'santri_id' => $santri->id,
        'uid_kartu' => 'UID-KANTIN-1',
        'fingerprint_template_ref' => 'FP-KANTIN-1',
    ]);

    Livewire::test(BayarKantin::class, ['device' => $device])
        ->set('nominal', 25000)
        ->call('mulai')
        ->set('uid', 'UID-KANTIN-1')
        ->assertSet('step', 'fingerprint')
        ->set('fingerprint_ref', 'FP-KANTIN-1')
        ->call('bayar')
        ->assertSet('step', 'selesai')
        ->assertHasNoErrors();

    $transaksi = Transaksi::where('santri_id', $santri->id)
        ->where('jenis', Transaksi::JENIS_PEMBAYARAN_KANTIN)
        ->sole();

    expect($santri->saldo->fresh()->saldo)->toBe(175000)
        ->and($device->unitUsaha->fresh()->saldo_unit)->toBe(25000)
        ->and($transaksi->metadata['device_id'])->toBe($device->id)
        ->and($transaksi->kwitansi)->not->toBeNull();
});

it('does not move money when the fingerprint is wrong', function () {
    $device = perangkatKantin();
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 50000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create([
        'santri_id' => $santri->id,
        'uid_kartu' => 'UID-KANTIN-2',
        'fingerprint_template_ref' => 'FP-BENAR',
    ]);

    Livewire::test(BayarKantin::class, ['device' => $device])
        ->set('nominal', 10000)->call('mulai')
        ->set('uid', 'UID-KANTIN-2')
        ->set('fingerprint_ref', 'FP-SALAH')->call('bayar')
        ->assertSet('step', 'fingerprint')
        ->assertHasErrors('fingerprint_ref');

    expect($santri->saldo->fresh()->saldo)->toBe(50000)
        ->and($device->unitUsaha->fresh()->saldo_unit)->toBe(0);
});

it('shows the remaining daily spending limit before fingerprint confirmation', function () {
    $device = perangkatKantin();
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    KebijakanKantin::factory()->create([
        'limit_harian' => 30000,
        'is_active' => true,
        'effective_from' => now()->subDay(),
    ]);
    // Pembayaran awal Rp 8.000 harus tetap menyisakan batas minimum saldo.
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create([
        'santri_id' => $santri->id,
        'uid_kartu' => 'UID-LIMIT-KANTIN',
        'fingerprint_template_ref' => 'FP-LIMIT-KANTIN',
    ]);
    app(KantinPembayaranService::class)->bayar($santri, $device->unitUsaha, 8000, null);

    Livewire::test(BayarKantin::class, ['device' => $device])
        ->set('nominal', 5000)
        ->call('mulai')
        ->set('uid', 'UID-LIMIT-KANTIN')
        ->assertSet('limitBelanja.limit', 30000)
        ->assertSet('limitBelanja.terpakai', 8000)
        ->assertSet('limitBelanja.sisa', 22000)
        ->assertSee('Sisa limit hari ini')
        ->assertSee('Rp 22.000')
        ->assertSee('Rp 17.000');
});

it('disables fingerprint payment when nominal exceeds the remaining daily limit', function () {
    $device = perangkatKantin();
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    KebijakanKantin::factory()->create([
        'limit_harian' => 10000,
        'is_active' => true,
        'effective_from' => now()->subDay(),
    ]);
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create([
        'santri_id' => $santri->id,
        'uid_kartu' => 'UID-OVER-LIMIT',
        'fingerprint_template_ref' => 'FP-OVER-LIMIT',
    ]);

    Livewire::test(BayarKantin::class, ['device' => $device])
        ->set('nominal', 12000)
        ->call('mulai')
        ->set('uid', 'UID-OVER-LIMIT')
        ->assertSee('Pemindaian sidik jari dinonaktifkan')
        ->assertSee('Ubah Nominal')
        ->set('fingerprint_ref', 'FP-OVER-LIMIT')
        ->call('bayar')
        ->assertHasErrors('fingerprint_ref');

    expect($santri->saldo->fresh()->saldo)->toBe(100000)
        ->and($device->unitUsaha->fresh()->saldo_unit)->toBe(0);
});
