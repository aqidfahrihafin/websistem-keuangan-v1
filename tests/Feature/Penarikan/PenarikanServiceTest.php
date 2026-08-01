<?php

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InvalidTransaksiException;
use App\Models\Device;
use App\Models\KartuSantri;
use App\Models\KebijakanPenarikan;
use App\Models\PenarikanRequest;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\WaliSantri;
use App\Services\PenarikanService;
use App\Services\SesiKasService;
use App\Services\PushNotificationService;
use App\Services\WalletService;
use Illuminate\Support\Carbon;

function tambahWali(Santri $santri): User
{
    $wali = makeUserWithRole('wali');

    WaliSantri::create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'hubungan' => 'wali',
        'is_auto_generated' => false,
        'is_primary' => true,
    ]);

    return $wali;
}

function withKebijakan(array $overrides = []): KebijakanPenarikan
{
    // A fixed, safely-past date rather than now()->subDay() - these tests
    // freeze the clock to a fictional date via Carbon::setTestNow() *after*
    // this helper runs, so deriving effective_from from the real wall-clock
    // "now" is fragile: it only stays before the frozen date by coincidence
    // of when the suite happens to run.
    return KebijakanPenarikan::factory()->create(array_merge([
        'jam_mulai' => '08:00:00',
        'jam_selesai' => '15:00:00',
        'limit_harian' => 50000,
        'is_active' => true,
        'effective_from' => '2020-01-01',
    ], $overrides));
}

afterEach(function () {
    Carbon::setTestNow();
});

it('does not require surat keterangan when within policy hours and under the daily limit', function () {
    withKebijakan();
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create();
    $request = app(PenarikanService::class)->createRequest($santri, 20000);

    expect($request->dalam_jam_kebijakan)->toBeTrue()
        ->and($request->melebihi_limit_harian)->toBeFalse()
        ->and($request->wajib_surat_keterangan)->toBeFalse();
});

it('requires surat keterangan when the request falls outside policy hours', function () {
    withKebijakan();
    Carbon::setTestNow(Carbon::parse('2026-07-11 20:00:00'));

    $santri = Santri::factory()->create();
    $request = app(PenarikanService::class)->createRequest($santri, 20000);

    expect($request->dalam_jam_kebijakan)->toBeFalse()
        ->and($request->wajib_surat_keterangan)->toBeTrue();
});

it('requires surat keterangan when the request exceeds the daily limit', function () {
    withKebijakan(); // limit_harian = 50000
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'fingerprint_template_ref' => 'FP-LIMIT']);
    $pengurus = makeUserWithRole('bendahara');
    $service = app(PenarikanService::class);

    // First withdrawal today, fulfilled through the real flow so it counts
    // toward the daily total the same way a real cash-out would.
    $first = $service->createRequest($santri, 40000);
    expect($first->wajib_surat_keterangan)->toBeFalse();
    $service->fulfill($service->approve($first, $pengurus), $pengurus, 'FP-LIMIT');

    $second = $service->createRequest($santri, 20000);

    expect($second->melebihi_limit_harian)->toBeTrue()
        ->and($second->wajib_surat_keterangan)->toBeTrue();
});

it('never allows a penarikan_tunai transaksi to be created without an approved request', function () {
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);

    expect(fn () => app(WalletService::class)->debit($santri, 10000, Transaksi::JENIS_PENARIKAN_TUNAI))
        ->toThrow(InvalidTransaksiException::class);

    expect($santri->saldo->fresh()->saldo)->toBe(100000);
});

it('fulfils an approved withdrawal request end to end with a matching fingerprint', function () {
    withKebijakan();
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    $kartu = KartuSantri::factory()->create([
        'santri_id' => $santri->id,
        'fingerprint_template_ref' => 'FP-TEST-1',
    ]);
    $pengurus = makeUserWithRole('bendahara');

    $service = app(PenarikanService::class);
    $request = $service->createRequest($santri, 20000);
    $service->approve($request, $pengurus);

    $result = $service->fulfill($request, $pengurus, 'FP-TEST-1');

    expect($result->status)->toBe(PenarikanRequest::STATUS_SELESAI)
        ->and($santri->saldo->fresh()->saldo)->toBe(80000);

    $transaksi = Transaksi::where('santri_id', $santri->id)->where('jenis', Transaksi::JENIS_PENARIKAN_TUNAI)->first();
    expect($transaksi)->not->toBeNull()
        ->and($transaksi->referensi_id)->toBe($request->id)
        ->and($transaksi->referensi_type)->toBe(PenarikanRequest::class);
});

it('completes an in-policy withdrawal fully self-service with no pengurus involved', function () {
    withKebijakan();
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'fingerprint_template_ref' => 'FP-MANDIRI']);

    $result = app(PenarikanService::class)->ajukanMandiri($santri, 20000, 'FP-MANDIRI');

    expect($result->status)->toBe(PenarikanRequest::STATUS_SELESAI)
        ->and($result->wajib_surat_keterangan)->toBeFalse()
        ->and($result->diproses_oleh)->toBeNull()
        ->and($santri->saldo->fresh()->saldo)->toBe(80000);

    $transaksi = Transaksi::where('santri_id', $santri->id)->where('jenis', Transaksi::JENIS_PENARIKAN_TUNAI)->first();
    expect($transaksi->diproses_oleh)->toBeNull();
});

it('threads the originating device through to the request for the audit trail', function () {
    withKebijakan();
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'fingerprint_template_ref' => 'FP-DEVICE']);
    $device = Device::factory()->create(['tipe' => Device::TIPE_KIOSK_PENARIKAN, 'status' => 'aktif']);
    $petugas = makeUserWithRole('petugas_kios');
    $device->petugasTerdaftar()->attach($petugas->id, ['aktif' => true, 'ditugaskan_at' => now()]);
    $sesi = app(SesiKasService::class)->buka($petugas, 'Kios Penarikan', 100000, $device);

    $result = app(PenarikanService::class)->ajukanMandiri($santri, 20000, 'FP-DEVICE', $device);

    expect($result->device_id)->toBe($device->id)
        ->and($result->sesi_kas_id)->toBe($sesi->id)
        ->and($result->diproses_oleh)->toBe($petugas->id)
        ->and($sesi->fresh()->total_keluar)->toBe(20000);

    $transaksi = Transaksi::where('santri_id', $santri->id)->where('jenis', Transaksi::JENIS_PENARIKAN_TUNAI)->first();
    expect($transaksi->referensi_id)->toBe($result->id);
});

it('cancels itself instead of proceeding when a self-service withdrawal would need surat keterangan', function () {
    withKebijakan(); // limit_harian = 50000
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'fingerprint_template_ref' => 'FP-OVERLIMIT']);

    expect(fn () => app(PenarikanService::class)->ajukanMandiri($santri, 60000, 'FP-OVERLIMIT'))
        ->toThrow(InvalidTransaksiException::class);

    expect($santri->saldo->fresh()->saldo)->toBe(200000);

    $request = PenarikanRequest::where('santri_id', $santri->id)->first();
    expect($request->status)->toBe(PenarikanRequest::STATUS_DIBATALKAN)
        ->and($request->wajib_surat_keterangan)->toBeTrue();
});

it('leaves saldo untouched when a self-service withdrawal fails fingerprint verification', function () {
    withKebijakan();
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'fingerprint_template_ref' => 'FP-RIGHT']);

    expect(fn () => app(PenarikanService::class)->ajukanMandiri($santri, 20000, 'FP-WRONG'))
        ->toThrow(InvalidTransaksiException::class);

    expect($santri->saldo->fresh()->saldo)->toBe(100000);
});

it('throws InsufficientBalanceException for a self-service withdrawal when saldo is too low', function () {
    withKebijakan();
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 5000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'fingerprint_template_ref' => 'FP-SHORT']);

    expect(fn () => app(PenarikanService::class)->ajukanMandiri($santri, 20000, 'FP-SHORT'))
        ->toThrow(InsufficientBalanceException::class);

    expect($santri->saldo->fresh()->saldo)->toBe(5000);
});

it('rejects fulfilment when the fingerprint reference does not match the santri active card', function () {
    withKebijakan();
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'fingerprint_template_ref' => 'FP-CORRECT']);
    $pengurus = makeUserWithRole('bendahara');

    $service = app(PenarikanService::class);
    $request = $service->createRequest($santri, 20000);
    $service->approve($request, $pengurus);

    expect(fn () => $service->fulfill($request, $pengurus, 'FP-WRONG'))
        ->toThrow(InvalidTransaksiException::class);

    expect($santri->saldo->fresh()->saldo)->toBe(100000)
        ->and($request->fresh()->status)->toBe(PenarikanRequest::STATUS_DISETUJUI);
});

it('blocks fulfilment when the santri saldo is insufficient', function () {
    withKebijakan();
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 5000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'fingerprint_template_ref' => 'FP-TEST-2']);
    $pengurus = makeUserWithRole('bendahara');

    $service = app(PenarikanService::class);
    $request = $service->createRequest($santri, 20000);
    $service->approve($request, $pengurus);

    expect(fn () => $service->fulfill($request, $pengurus, 'FP-TEST-2'))
        ->toThrow(InsufficientBalanceException::class);

    expect($santri->saldo->fresh()->saldo)->toBe(5000);
});

it('reports the remaining daily withdrawal limit, reduced only by fulfilled withdrawals', function () {
    withKebijakan(); // limit_harian = 50000, 08:00-15:00
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 200000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'fingerprint_template_ref' => 'FP-RINGKASAN']);
    $pengurus = makeUserWithRole('bendahara');
    $service = app(PenarikanService::class);

    $awal = $service->ringkasanLimitHarian($santri);
    expect($awal['limit'])->toBe(50000)
        ->and($awal['terpakai'])->toBe(0)
        ->and($awal['sisa'])->toBe(50000)
        ->and($awal['dalam_jam'])->toBeTrue();

    // A pending (not yet fulfilled) request must not reduce the remaining limit.
    $service->createRequest($santri, 20000);
    expect($service->ringkasanLimitHarian($santri)['sisa'])->toBe(50000);

    // Only a completed withdrawal counts against today's usage.
    $request = $service->createRequest($santri, 30000);
    $service->fulfill($service->approve($request, $pengurus), $pengurus, 'FP-RINGKASAN');

    $setelah = $service->ringkasanLimitHarian($santri);
    expect($setelah['terpakai'])->toBe(30000)
        ->and($setelah['sisa'])->toBe(20000);
});

it('reports no daily limit and always-open hours when there is no applicable kebijakan', function () {
    $santri = Santri::factory()->create();

    $ringkasan = app(PenarikanService::class)->ringkasanLimitHarian($santri);

    expect($ringkasan['kebijakan'])->toBeNull()
        ->and($ringkasan['limit'])->toBeNull()
        ->and($ringkasan['sisa'])->toBeNull()
        ->and($ringkasan['dalam_jam'])->toBeTrue();
});

it('reports being outside operating hours when the current time falls outside the active kebijakan', function () {
    withKebijakan(); // 08:00-15:00
    Carbon::setTestNow(Carbon::parse('2026-07-11 20:00:00'));

    $santri = Santri::factory()->create();

    expect(app(PenarikanService::class)->ringkasanLimitHarian($santri)['dalam_jam'])->toBeFalse();
});

it('blocks approval when surat keterangan is required but not yet approved', function () {
    withKebijakan();
    Carbon::setTestNow(Carbon::parse('2026-07-11 20:00:00')); // outside hours -> wajib surat

    $santri = Santri::factory()->create();
    $pengurus = makeUserWithRole('bendahara');
    $service = app(PenarikanService::class);

    $request = $service->createRequest($santri, 20000);

    expect(fn () => $service->approve($request, $pengurus))
        ->toThrow(InvalidTransaksiException::class);

    $service->unggahSuratKeterangan($request, 'surat-keterangan/test.pdf');
    $service->reviewSuratKeterangan($request->fresh(), true, $pengurus);

    $approved = $service->approve($request->fresh(), $pengurus);
    expect($approved->status)->toBe(PenarikanRequest::STATUS_DISETUJUI);
});

it('notifies the wali when an admin approves a penarikan request', function () {
    withKebijakan();
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create();
    $wali = tambahWali($santri);
    $pengurus = makeUserWithRole('bendahara');

    $push = $this->mock(PushNotificationService::class);
    $push->shouldReceive('notify')
        ->once()
        ->with(
            Mockery::on(fn ($u) => $u->is($wali)),
            'Penarikan Disetujui',
            Mockery::type('string'),
            Mockery::on(fn ($data) => $data['type'] === 'penarikan_disetujui' && $data['santri_id'] === $santri->id),
        );

    $request = app(PenarikanService::class)->createRequest($santri, 20000);
    app(PenarikanService::class)->approve($request, $pengurus);
});

it('does not fire the separate disetujui notification for a self-service kiosk withdrawal, to avoid double-notifying alongside the debit', function () {
    withKebijakan();
    Carbon::setTestNow(Carbon::parse('2026-07-11 10:00:00'));

    $santri = Santri::factory()->create();
    tambahWali($santri);
    app(WalletService::class)->credit($santri, 100000, Transaksi::JENIS_TOPUP_TUNAI);
    KartuSantri::factory()->create(['santri_id' => $santri->id, 'fingerprint_template_ref' => 'FP-NOTIFY']);

    $push = $this->mock(PushNotificationService::class);
    // The debit itself still notifies ("transaksi keluar") - only the
    // separate "Penarikan Disetujui" notification from approve() must be
    // suppressed here, since firing both for one self-service withdrawal
    // would double-notify almost simultaneously.
    $push->shouldReceive('notify')
        ->once()
        ->with(Mockery::any(), Mockery::not('Penarikan Disetujui'), Mockery::type('string'), Mockery::type('array'));

    app(PenarikanService::class)->ajukanMandiri($santri, 20000, 'FP-NOTIFY');
});
