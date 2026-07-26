<?php

use App\Livewire\Kios\CekSaldo;
use App\Models\Device;
use App\Models\KartuSantri;
use App\Models\KebijakanPenarikan;
use App\Models\PenarikanRequest;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Services\PenarikanService;
use App\Services\WalletService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;

beforeEach(function () {
    // The scan rate limiter is IP-keyed and backed by cache, which (unlike
    // the database) is not reset between tests - clear it so one test's
    // scan attempts can never bleed into another's rate-limit assertions.
    Cache::flush();
});

function kioskPenarikanAktif(): Device
{
    return Device::factory()->create([
        'tipe' => Device::TIPE_KIOSK_PENARIKAN,
        'status' => 'aktif',
    ]);
}

afterEach(function () {
    Carbon::setTestNow();
});

it('is reachable without logging in', function () {
    $this->get('/kios')->assertOk();
});

it('shows saldo and limit info after tapping a card with a valid, active uid', function () {
    KebijakanPenarikan::factory()->create([
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '15:00:00',
        'limit_harian' => 50000,
        'is_active' => true,
        'effective_from' => '2020-01-01',
    ]);
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 75000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-VALID-1']);

    Livewire::test(CekSaldo::class)
        ->set('uid', 'UID-VALID-1')
        ->call('scan')
        ->assertSet('step', 'found')
        ->assertViewHas('saldo', 75000)
        ->assertViewHas('limitInfo', fn (array $info) => $info['limit'] === 50000 && $info['sisa'] === 50000);
});

it('auto-scans as soon as uid is set, without needing an explicit scan call', function () {
    // Simulates wire:model.live.debounce on the view - the RFID reader
    // "typing" the UID should submit on its own once typing settles,
    // without depending on the reader also sending a trailing Enter.
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-AUTO-1']);

    Livewire::test(CekSaldo::class)
        ->set('uid', 'UID-AUTO-1')
        ->assertSet('step', 'found')
        ->assertSet('uid', '');
});

it('shows a not-found state for an unknown or inactive card uid', function () {
    $santri = Santri::factory()->create();
    $kartu = KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-NONAKTIF']);
    $kartu->update(['status' => KartuSantri::STATUS_NONAKTIF]);

    Livewire::test(CekSaldo::class)
        ->set('uid', 'UID-NONAKTIF')
        ->call('scan')
        ->assertSet('step', 'not_found');

    Livewire::test(CekSaldo::class)
        ->set('uid', 'UID-TIDAK-ADA')
        ->call('scan')
        ->assertSet('step', 'not_found');
});

it('moves an in-policy nominal to the fingerprint verification step without persisting anything yet', function () {
    KebijakanPenarikan::factory()->create([
        'jam_mulai' => '08:00:00', 'jam_selesai' => '15:00:00', 'limit_harian' => 50000,
        'is_active' => true, 'effective_from' => '2020-01-01',
    ]);
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-AJUKAN']);

    Livewire::test(CekSaldo::class, ['device' => kioskPenarikanAktif()])
        ->set('uid', 'UID-AJUKAN')
        ->call('scan')
        ->set('nominal', 25000)
        ->call('ajukan')
        ->assertSet('step', 'verifikasi_fingerprint')
        ->assertHasNoErrors();

    // Nothing persisted yet - the request is only created once a
    // fingerprint is actually presented via cairkan().
    expect(PenarikanRequest::where('santri_id', $santri->id)->exists())->toBeFalse();
});

it('completes an in-policy withdrawal self-service once a matching fingerprint is presented', function () {
    KebijakanPenarikan::factory()->create([
        'jam_mulai' => '08:00:00', 'jam_selesai' => '15:00:00', 'limit_harian' => 50000,
        'is_active' => true, 'effective_from' => '2020-01-01',
    ]);
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-CAIRKAN', 'fingerprint_template_ref' => 'FP-CAIRKAN']);
    $device = kioskPenarikanAktif();

    Livewire::test(CekSaldo::class, ['device' => $device])
        ->set('uid', 'UID-CAIRKAN')
        ->call('scan')
        ->set('nominal', 25000)
        ->call('ajukan')
        ->set('fingerprint_ref', 'FP-CAIRKAN')
        ->call('cairkan')
        ->assertSet('step', 'selesai_mandiri')
        ->assertHasNoErrors();

    expect($santri->saldo->fresh()->saldo)->toBe(75000);

    $request = PenarikanRequest::where('santri_id', $santri->id)->where('nominal_diminta', 25000)->first();
    expect($request->status)->toBe(PenarikanRequest::STATUS_SELESAI)
        ->and($request->diproses_oleh)->toBeNull()
        ->and($request->device_id)->toBe($device->id);
});

it('keeps the fingerprint step alive and counts attempts on a mismatch, without moving any money', function () {
    KebijakanPenarikan::factory()->create([
        'jam_mulai' => '08:00:00', 'jam_selesai' => '15:00:00', 'limit_harian' => 50000,
        'is_active' => true, 'effective_from' => '2020-01-01',
    ]);
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-SALAH', 'fingerprint_template_ref' => 'FP-BENAR']);

    Livewire::test(CekSaldo::class, ['device' => kioskPenarikanAktif()])
        ->set('uid', 'UID-SALAH')
        ->call('scan')
        ->set('nominal', 25000)
        ->call('ajukan')
        ->set('fingerprint_ref', 'FP-SALAH')
        ->call('cairkan')
        ->assertSet('step', 'verifikasi_fingerprint')
        ->assertSet('percobaanFingerprint', 1)
        ->assertHasErrors('fingerprint_ref');

    expect($santri->saldo->fresh()->saldo)->toBe(100000)
        ->and(PenarikanRequest::where('santri_id', $santri->id)->count())->toBe(1)
        ->and(PenarikanRequest::where('santri_id', $santri->id)->first()->status)->toBe(PenarikanRequest::STATUS_DISETUJUI);
});

it('reaches a dead-end state after 3 failed fingerprint attempts', function () {
    KebijakanPenarikan::factory()->create([
        'jam_mulai' => '08:00:00', 'jam_selesai' => '15:00:00', 'limit_harian' => 50000,
        'is_active' => true, 'effective_from' => '2020-01-01',
    ]);
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-GAGAL', 'fingerprint_template_ref' => 'FP-BENAR']);

    $component = Livewire::test(CekSaldo::class, ['device' => kioskPenarikanAktif()])
        ->set('uid', 'UID-GAGAL')
        ->call('scan')
        ->set('nominal', 25000)
        ->call('ajukan');

    $component->set('fingerprint_ref', 'FP-SALAH')->call('cairkan')->assertSet('step', 'verifikasi_fingerprint');
    $component->set('fingerprint_ref', 'FP-SALAH')->call('cairkan')->assertSet('step', 'verifikasi_fingerprint');
    $component->set('fingerprint_ref', 'FP-SALAH')->call('cairkan')->assertSet('step', 'gagal_terus');

    expect($santri->saldo->fresh()->saldo)->toBe(100000);
});

it('lets the wali/santri back out of the fingerprint step to change the nominal', function () {
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-BATAL']);

    Livewire::test(CekSaldo::class, ['device' => kioskPenarikanAktif()])
        ->set('uid', 'UID-BATAL')
        ->call('scan')
        ->set('nominal', 25000)
        ->call('ajukan')
        ->assertSet('step', 'verifikasi_fingerprint')
        ->call('batalFingerprint')
        ->assertSet('step', 'found')
        ->assertSet('nominal', null);
});

it('rate-limits repeated cairkan attempts for the same santri', function () {
    KebijakanPenarikan::factory()->create([
        'jam_mulai' => '08:00:00', 'jam_selesai' => '15:00:00', 'limit_harian' => 500000,
        'is_active' => true, 'effective_from' => '2020-01-01',
    ]);
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 1000000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-FLOOD-CAIRKAN', 'fingerprint_template_ref' => 'FP-BENAR']);
    $device = kioskPenarikanAktif();

    for ($i = 0; $i < 5; $i++) {
        Livewire::test(CekSaldo::class, ['device' => $device])
            ->set('uid', 'UID-FLOOD-CAIRKAN')
            ->call('scan')
            ->set('nominal', 1000)
            ->call('ajukan')
            ->set('fingerprint_ref', 'FP-SALAH')
            ->call('cairkan');
    }

    Livewire::test(CekSaldo::class, ['device' => $device])
        ->set('uid', 'UID-FLOOD-CAIRKAN')
        ->call('scan')
        ->set('nominal', 1000)
        ->call('ajukan')
        ->set('fingerprint_ref', 'FP-BENAR')
        ->call('cairkan')
        ->assertSet('step', 'rate_limited');
});

it('blocks a penarikan request from the kiosk when saldo is zero', function () {
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-KOSONG']);

    Livewire::test(CekSaldo::class)
        ->set('uid', 'UID-KOSONG')
        ->call('scan')
        ->set('nominal', 10000)
        ->call('ajukan')
        ->assertSet('step', 'found')
        ->assertHasErrors('nominal');

    expect(PenarikanRequest::where('santri_id', $santri->id)->exists())->toBeFalse();
});

it('blocks a penarikan request from the kiosk when outside the active kebijakan operating hours', function () {
    KebijakanPenarikan::factory()->create([
        'jam_mulai' => '08:00:00', 'jam_selesai' => '15:00:00', 'limit_harian' => 50000,
        'is_active' => true, 'effective_from' => '2020-01-01',
    ]);
    Carbon::setTestNow(Carbon::parse('2026-07-11 20:00:00'));

    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-LUAR-JAM']);

    Livewire::test(CekSaldo::class)
        ->set('uid', 'UID-LUAR-JAM')
        ->call('scan')
        ->set('nominal', 10000)
        ->call('ajukan')
        ->assertSet('step', 'found')
        ->assertHasErrors('nominal');

    expect(PenarikanRequest::where('santri_id', $santri->id)->exists())->toBeFalse();
});

it('blocks a penarikan request from the kiosk when the daily limit is already fully used', function () {
    KebijakanPenarikan::factory()->create([
        'jam_mulai' => '08:00:00', 'jam_selesai' => '15:00:00', 'limit_harian' => 50000,
        'is_active' => true, 'effective_from' => '2020-01-01',
    ]);
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-LIMIT-HABIS', 'fingerprint_template_ref' => 'FP-LIMIT-HABIS']);
    $pengurus = makeUserWithRole('bendahara');
    $service = app(PenarikanService::class);

    // Use up the entire daily limit through a real, fulfilled withdrawal first.
    $request = $service->createRequest($santri, 50000);
    $service->fulfill($service->approve($request, $pengurus), $pengurus, 'FP-LIMIT-HABIS');

    Livewire::test(CekSaldo::class)
        ->set('uid', 'UID-LIMIT-HABIS')
        ->call('scan')
        ->set('nominal', 10000)
        ->call('ajukan')
        ->assertSet('step', 'found')
        ->assertHasErrors('nominal');

    expect(PenarikanRequest::where('santri_id', $santri->id)->where('nominal_diminta', 10000)->exists())->toBeFalse();
});

it('blocks a kiosk request whose nominal exceeds the remaining daily limit, even when some limit is left', function () {
    KebijakanPenarikan::factory()->create([
        'jam_mulai' => '08:00:00', 'jam_selesai' => '15:00:00', 'limit_harian' => 50000,
        'is_active' => true, 'effective_from' => '2020-01-01',
    ]);
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-SISA-SEDIKIT']);

    Livewire::test(CekSaldo::class)
        ->set('uid', 'UID-SISA-SEDIKIT')
        ->call('scan')
        ->set('nominal', 60000) // exceeds the full 50000 daily limit, none of it used yet
        ->call('ajukan')
        ->assertSet('step', 'found')
        ->assertHasErrors('nominal');

    expect(PenarikanRequest::where('santri_id', $santri->id)->exists())->toBeFalse();
});

it('shows only penarikan requests from the last 7 days in the kiosk riwayat', function () {
    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-RIWAYAT']);

    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));
    $baru = PenarikanRequest::create([
        'santri_id' => $santri->id, 'nominal_diminta' => 15000, 'status' => 'selesai',
        'diminta_at' => now(), 'dalam_jam_kebijakan' => true, 'melebihi_limit_harian' => false, 'wajib_surat_keterangan' => false,
    ]);
    $lama = PenarikanRequest::create([
        'santri_id' => $santri->id, 'nominal_diminta' => 99000, 'status' => 'selesai',
        'diminta_at' => now()->subDays(10), 'dalam_jam_kebijakan' => true, 'melebihi_limit_harian' => false, 'wajib_surat_keterangan' => false,
    ]);

    $result = Livewire::test(CekSaldo::class)
        ->set('uid', 'UID-RIWAYAT')
        ->call('scan');

    $result->assertViewHas('riwayat', function ($riwayat) use ($baru, $lama) {
        return $riwayat->pluck('id')->contains($baru->id) && ! $riwayat->pluck('id')->contains($lama->id);
    });
});

it('resets back to the idle scan state and clears the resolved santri', function () {
    $santri = Santri::factory()->create();
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-RESET']);

    Livewire::test(CekSaldo::class)
        ->set('uid', 'UID-RESET')
        ->call('scan')
        ->assertSet('step', 'found')
        ->call('selesai')
        ->assertSet('step', 'idle')
        ->assertSet('santriId', null);
});

it('rate-limits repeated card scan attempts from the same requester', function () {
    for ($i = 0; $i < 20; $i++) {
        Livewire::test(CekSaldo::class)->set('uid', "UID-FLOOD-{$i}")->call('scan');
    }

    Livewire::test(CekSaldo::class)
        ->set('uid', 'UID-ONE-MORE')
        ->call('scan')
        ->assertSet('step', 'rate_limited');
});

it('resolves the device from the URL and completes a self-service withdrawal attributed to it', function () {
    KebijakanPenarikan::factory()->create([
        'jam_mulai' => '08:00:00', 'jam_selesai' => '15:00:00', 'limit_harian' => 50000,
        'is_active' => true, 'effective_from' => '2020-01-01',
    ]);
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-URL-DEVICE', 'fingerprint_template_ref' => 'FP-URL-DEVICE']);
    $device = kioskPenarikanAktif();

    $this->get("/kios/{$device->kode_device}")->assertOk();

    Livewire::test(CekSaldo::class, ['device' => $device])
        ->set('uid', 'UID-URL-DEVICE')
        ->call('scan')
        ->set('nominal', 25000)
        ->call('ajukan')
        ->set('fingerprint_ref', 'FP-URL-DEVICE')
        ->call('cairkan')
        ->assertSet('step', 'selesai_mandiri');

    $request = PenarikanRequest::where('santri_id', $santri->id)->first();
    expect($request->device_id)->toBe($device->id);
});

it('does not offer self-service on a kiosk_saldo-type device', function () {
    KebijakanPenarikan::factory()->create([
        'jam_mulai' => '08:00:00', 'jam_selesai' => '15:00:00', 'limit_harian' => 50000,
        'is_active' => true, 'effective_from' => '2020-01-01',
    ]);
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-KIOSK-SALDO']);
    $device = Device::factory()->create(['tipe' => Device::TIPE_KIOSK_SALDO, 'status' => 'aktif']);

    Livewire::test(CekSaldo::class, ['device' => $device])
        ->set('uid', 'UID-KIOSK-SALDO')
        ->call('scan')
        ->set('nominal', 25000)
        ->call('ajukan')
        ->assertSet('step', 'found')
        ->assertHasErrors('nominal');

    expect(PenarikanRequest::where('santri_id', $santri->id)->exists())->toBeFalse();
});

it('does not offer self-service on a nonaktif device', function () {
    KebijakanPenarikan::factory()->create([
        'jam_mulai' => '08:00:00', 'jam_selesai' => '15:00:00', 'limit_harian' => 50000,
        'is_active' => true, 'effective_from' => '2020-01-01',
    ]);
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-NONAKTIF-DEVICE']);
    $device = Device::factory()->create(['tipe' => Device::TIPE_KIOSK_PENARIKAN, 'status' => 'nonaktif']);

    Livewire::test(CekSaldo::class, ['device' => $device])
        ->set('uid', 'UID-NONAKTIF-DEVICE')
        ->call('scan')
        ->set('nominal', 25000)
        ->call('ajukan')
        ->assertSet('step', 'found')
        ->assertHasErrors('nominal');

    expect(PenarikanRequest::where('santri_id', $santri->id)->exists())->toBeFalse();
});

it('does not offer self-service on the bare /kios URL with no device at all', function () {
    KebijakanPenarikan::factory()->create([
        'jam_mulai' => '08:00:00', 'jam_selesai' => '15:00:00', 'limit_harian' => 50000,
        'is_active' => true, 'effective_from' => '2020-01-01',
    ]);
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-NO-DEVICE']);

    Livewire::test(CekSaldo::class)
        ->set('uid', 'UID-NO-DEVICE')
        ->call('scan')
        ->set('nominal', 25000)
        ->call('ajukan')
        ->assertSet('step', 'found')
        ->assertHasErrors('nominal');

    expect(PenarikanRequest::where('santri_id', $santri->id)->exists())->toBeFalse();
});

it("updates the device's last_seen_at on a card scan", function () {
    $device = kioskPenarikanAktif();
    $santri = Santri::factory()->create();
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-LAST-SEEN']);

    expect($device->last_seen_at)->toBeNull();

    Livewire::test(CekSaldo::class, ['device' => $device])
        ->set('uid', 'UID-LAST-SEEN')
        ->call('scan');

    expect($device->fresh()->last_seen_at)->not->toBeNull();
});

it('rejects cairkan directly, bypassing ajukan, when the device is not eligible for self-service', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create(['status' => Santri::STATUS_AKTIF]);
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'uid_kartu' => 'UID-BYPASS', 'fingerprint_template_ref' => 'FP-BYPASS']);
    $device = Device::factory()->create(['tipe' => Device::TIPE_KIOSK_SALDO, 'status' => 'aktif']);

    Livewire::test(CekSaldo::class, ['device' => $device])
        ->set('uid', 'UID-BYPASS')
        ->call('scan')
        ->set('nominal', 25000)
        ->set('fingerprint_ref', 'FP-BYPASS')
        ->call('cairkan')
        ->assertSet('step', 'found')
        ->assertHasErrors('nominal');

    expect($santri->saldo->fresh()->saldo)->toBe(100000)
        ->and(PenarikanRequest::where('santri_id', $santri->id)->exists())->toBeFalse();
});
