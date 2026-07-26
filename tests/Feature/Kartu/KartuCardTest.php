<?php

use App\Livewire\Admin\Kartu\Index as KartuIndex;
use App\Models\KartuSantri;
use App\Models\Santri;
use Livewire\Livewire;

it('suggests KRT-000001 as the next nomor_kartu when none exist yet', function () {
    expect(KartuSantri::nomorKartuBerikutnya())->toBe('KRT-000001');
});

it('suggests the next nomor_kartu after the highest one actually issued, not just a row count', function () {
    KartuSantri::factory()->create(['nomor_kartu' => 'KRT-000005']);
    // A deactivated/replaced card still counts toward "highest issued" -
    // the count of rows isn't a safe proxy once replacements exist.
    KartuSantri::factory()->create(['nomor_kartu' => 'KRT-000002', 'status' => KartuSantri::STATUS_NONAKTIF]);

    expect(KartuSantri::nomorKartuBerikutnya())->toBe('KRT-000006');
});

it('pre-fills an auto-generated nomor_kartu when opening the activation modal', function () {
    $admin = makeUserWithRole('admin');
    KartuSantri::factory()->create(['nomor_kartu' => 'KRT-000003']);

    Livewire::actingAs($admin)->test(KartuIndex::class)
        ->call('openAktivasi')
        ->assertSet('nomor_kartu', 'KRT-000004');
});

it('warns instead of creating a duplicate when the santri already has an active kartu', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create(['nis' => '10099', 'status' => Santri::STATUS_AKTIF]);
    $existing = KartuSantri::factory()->create([
        'santri_id' => $santri->id,
        'nomor_kartu' => 'KRT-OLD',
        'status' => KartuSantri::STATUS_AKTIF,
    ]);

    Livewire::actingAs($admin)->test(KartuIndex::class)
        ->call('openAktivasi')
        ->set('nis', '10099')
        ->call('cariSantri')
        ->assertSet('santriKartuAktif.id', $existing->id);

    expect(KartuSantri::where('santri_id', $santri->id)->count())->toBe(1);
});

it('rejects activation server-side even if the client bypasses the warning', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create(['nis' => '10098', 'status' => Santri::STATUS_AKTIF]);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'status' => KartuSantri::STATUS_AKTIF]);

    Livewire::actingAs($admin)->test(KartuIndex::class)
        ->call('openAktivasi')
        ->set('nis', '10098')
        ->call('cariSantri')
        ->set('nomor_kartu', 'KRT-SNEAKY')
        ->call('aktivasi')
        ->assertHasErrors(['nis']);

    expect(KartuSantri::where('nomor_kartu', 'KRT-SNEAKY')->exists())->toBeFalse();
});

it('lets an admin deactivate the old kartu directly from the already-active warning, then continue straight to the activation form', function () {
    $admin = makeUserWithRole('admin');
    $santri = Santri::factory()->create(['nis' => '10097', 'status' => Santri::STATUS_AKTIF]);
    $existing = KartuSantri::factory()->create(['santri_id' => $santri->id, 'status' => KartuSantri::STATUS_AKTIF]);

    Livewire::actingAs($admin)->test(KartuIndex::class)
        ->call('openAktivasi')
        ->set('nis', '10097')
        ->call('cariSantri')
        ->assertSet('santriKartuAktif.id', $existing->id)
        ->call('nonaktifkanKartuLama')
        ->assertSet('showModal', true)
        ->assertSet('santriKartuAktif', null);

    expect($existing->fresh()->status)->toBe(KartuSantri::STATUS_NONAKTIF);
});

it('toggles the detail row for a kartu', function () {
    $admin = makeUserWithRole('admin');
    $kartu = KartuSantri::factory()->create();

    Livewire::actingAs($admin)->test(KartuIndex::class)
        ->call('toggleDetail', $kartu->id)
        ->assertSet('expandedId', $kartu->id)
        ->call('toggleDetail', $kartu->id)
        ->assertSet('expandedId', null);
});

it('downloads a printable card for an active kartu but not an inactive one', function () {
    $admin = makeUserWithRole('admin');
    $aktif = KartuSantri::factory()->create(['status' => KartuSantri::STATUS_AKTIF]);
    $nonaktif = KartuSantri::factory()->create(['status' => KartuSantri::STATUS_NONAKTIF]);

    $this->actingAs($admin)->get(route('admin.kartu.cetak', $aktif))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $this->actingAs($admin)->get(route('admin.kartu.cetak', $nonaktif))
        ->assertNotFound();
});

it('downloads a bulk pdf of every active kartu', function () {
    $admin = makeUserWithRole('admin');
    KartuSantri::factory()->count(2)->create(['status' => KartuSantri::STATUS_AKTIF]);
    KartuSantri::factory()->create(['status' => KartuSantri::STATUS_NONAKTIF]);

    $this->actingAs($admin)->get(route('admin.kartu.cetak-semua'))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('auto-deactivates the active kartu when the santri status becomes nonaktif, lulus, or keluar', function (string $status) {
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    $kartu = KartuSantri::factory()->create(['santri_id' => $santri->id, 'status' => KartuSantri::STATUS_AKTIF]);

    $santri->update(['status' => $status]);

    expect($kartu->fresh()->status)->toBe(KartuSantri::STATUS_NONAKTIF)
        ->and($kartu->fresh()->alasan_nonaktif)->toContain($status);
})->with([
    Santri::STATUS_NONAKTIF,
    Santri::STATUS_LULUS,
    Santri::STATUS_KELUAR,
]);

it('does not touch the kartu when the santri status changes to something that still counts as active', function () {
    $santri = Santri::factory()->create(['status' => Santri::STATUS_BARU]);
    $kartu = KartuSantri::factory()->create(['santri_id' => $santri->id, 'status' => KartuSantri::STATUS_AKTIF]);

    $santri->update(['status' => Santri::STATUS_AKTIF]);

    expect($kartu->fresh()->status)->toBe(KartuSantri::STATUS_AKTIF);
});

it('filters the kartu list by print status', function () {
    $admin = makeUserWithRole('admin');
    $sudah = KartuSantri::factory()->create(['nomor_kartu' => 'KRT-SUDAH', 'jumlah_cetak' => 2, 'dicetak_pertama_at' => now(), 'dicetak_terakhir_at' => now()]);
    $belum = KartuSantri::factory()->create(['nomor_kartu' => 'KRT-BELUM']);

    $component = Livewire::actingAs($admin)->test(KartuIndex::class)
        ->assertSee('KRT-SUDAH')
        ->assertSee('KRT-BELUM');

    $component->set('statusCetak', 'belum')
        ->assertSee('KRT-BELUM')
        ->assertDontSee('KRT-SUDAH');

    $component->set('statusCetak', 'sudah')
        ->assertSee('KRT-SUDAH')
        ->assertDontSee('KRT-BELUM');
});

it('shows a reprint confirmation only for a kartu that has already been printed before', function () {
    $admin = makeUserWithRole('admin');
    $sudah = KartuSantri::factory()->create(['status' => KartuSantri::STATUS_AKTIF, 'jumlah_cetak' => 3, 'dicetak_pertama_at' => now(), 'dicetak_terakhir_at' => now()]);
    $belum = KartuSantri::factory()->create(['status' => KartuSantri::STATUS_AKTIF]);

    Livewire::actingAs($admin)->test(KartuIndex::class)
        ->assertSee('Cetak Ulang')
        ->assertSee('Dicetak 3x')
        ->assertSee('Belum Pernah')
        ->assertSee('sudah pernah dicetak 3x');
});
