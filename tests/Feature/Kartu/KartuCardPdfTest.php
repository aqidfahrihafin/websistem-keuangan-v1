<?php

use App\Models\KartuSantri;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('renders the single kartu santri PDF for an active card', function () {
    $admin = makeUserWithRole('admin');
    $kartu = KartuSantri::factory()->create();

    $this->actingAs($admin)
        ->get(route('admin.kartu.cetak', $kartu))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('renders the bulk kartu santri PDF for all active cards', function () {
    $admin = makeUserWithRole('admin');
    KartuSantri::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get(route('admin.kartu.cetak-semua'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('previews the single kartu santri PDF inline instead of forcing a download', function () {
    $admin = makeUserWithRole('admin');
    $kartu = KartuSantri::factory()->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.kartu.preview', $kartu))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain('inline');
});

it('previews the bulk kartu santri PDF inline instead of forcing a download', function () {
    $admin = makeUserWithRole('admin');
    KartuSantri::factory()->count(2)->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.kartu.preview-semua'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain('inline');
});

it('still forces an attachment download on the original cetak routes, not inline', function () {
    $admin = makeUserWithRole('admin');
    $kartu = KartuSantri::factory()->create();

    $response = $this->actingAs($admin)
        ->get(route('admin.kartu.cetak', $kartu))
        ->assertOk();

    expect($response->headers->get('content-disposition'))->toContain('attachment');
});

it('records a print on the single cetak route but not on preview', function () {
    $admin = makeUserWithRole('admin');
    $kartu = KartuSantri::factory()->create();

    $this->actingAs($admin)->get(route('admin.kartu.preview', $kartu))->assertOk();
    expect($kartu->fresh()->sudahPernahDicetak())->toBeFalse();

    $this->actingAs($admin)->get(route('admin.kartu.cetak', $kartu))->assertOk();

    $fresh = $kartu->fresh();
    expect($fresh->jumlah_cetak)->toBe(1)
        ->and($fresh->dicetak_pertama_at)->not->toBeNull()
        ->and($fresh->dicetak_terakhir_at)->not->toBeNull()
        ->and($fresh->dicetak_terakhir_oleh)->toBe($admin->id);

    // A second print, well after the double-click dedup window (see the
    // dedicated test below), is a genuine reprint - counter increments,
    // dicetak_pertama_at stays put.
    $pertamaSebelum = $fresh->dicetak_pertama_at;
    Carbon::setTestNow(now()->addMinutes(10));
    $this->actingAs($admin)->get(route('admin.kartu.cetak', $kartu))->assertOk();

    $freshLagi = $kartu->fresh();
    expect($freshLagi->jumlah_cetak)->toBe(2)
        ->and($freshLagi->dicetak_pertama_at)->toEqual($pertamaSebelum);
});

it('folds a double-click into a single print instead of double-counting it', function () {
    $admin = makeUserWithRole('admin');
    $kartu = KartuSantri::factory()->create();

    // Two requests landing within the same instant - exactly what an
    // accidental double-click on the download link produces.
    $this->actingAs($admin)->get(route('admin.kartu.cetak', $kartu))->assertOk();
    $this->actingAs($admin)->get(route('admin.kartu.cetak', $kartu))->assertOk();

    expect($kartu->fresh()->jumlah_cetak)->toBe(1);
});

it('records a print for every kartu in a bulk cetak-semua but not a bulk preview', function () {
    $admin = makeUserWithRole('admin');
    $kartus = KartuSantri::factory()->count(2)->create(['status' => KartuSantri::STATUS_AKTIF]);

    $this->actingAs($admin)->get(route('admin.kartu.preview-semua'))->assertOk();
    expect($kartus->fresh()->every(fn ($k) => ! $k->sudahPernahDicetak()))->toBeTrue();

    $this->actingAs($admin)->get(route('admin.kartu.cetak-semua'))->assertOk();

    expect($kartus->fresh()->every(fn ($k) => $k->jumlah_cetak === 1))->toBeTrue();
});

it('scopes bulk cetak-semua to only never-printed cards via status_cetak=belum', function () {
    $admin = makeUserWithRole('admin');
    $sudah = KartuSantri::factory()->create(['status' => KartuSantri::STATUS_AKTIF, 'jumlah_cetak' => 1, 'dicetak_pertama_at' => now(), 'dicetak_terakhir_at' => now()]);
    $belum = KartuSantri::factory()->create(['status' => KartuSantri::STATUS_AKTIF]);

    $this->actingAs($admin)->get(route('admin.kartu.cetak-semua', ['status_cetak' => 'belum']))->assertOk();

    expect($sudah->fresh()->jumlah_cetak)->toBe(1)
        ->and($belum->fresh()->jumlah_cetak)->toBe(1);
});

it('scopes bulk cetak-semua to only already-printed cards via status_cetak=sudah', function () {
    $admin = makeUserWithRole('admin');
    // Well outside the double-click dedup window, so this bulk print
    // genuinely counts as a second, distinct print event.
    $sudah = KartuSantri::factory()->create(['status' => KartuSantri::STATUS_AKTIF, 'jumlah_cetak' => 1, 'dicetak_pertama_at' => now()->subHour(), 'dicetak_terakhir_at' => now()->subHour()]);
    $belum = KartuSantri::factory()->create(['status' => KartuSantri::STATUS_AKTIF]);

    $this->actingAs($admin)->get(route('admin.kartu.cetak-semua', ['status_cetak' => 'sudah']))->assertOk();

    expect($sudah->fresh()->jumlah_cetak)->toBe(2)
        ->and($belum->fresh()->jumlah_cetak)->toBe(0);
});
