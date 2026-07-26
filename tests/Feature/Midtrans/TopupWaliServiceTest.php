<?php

use App\Models\JenisTagihan;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\TagihanPembayaran;
use App\Models\TopupWali;
use App\Models\Kwitansi;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\WaliSantri;
use App\Services\PushNotificationService;
use App\Services\TagihanService;
use App\Services\TopupWaliService;
use App\Services\WalletService;
use Symfony\Component\HttpKernel\Exception\HttpException;

function tambahWaliUntukTopup(Santri $santri): User
{
    $wali = User::factory()->create();

    WaliSantri::create([
        'user_id' => $wali->id,
        'santri_id' => $santri->id,
        'hubungan' => 'wali',
        'is_auto_generated' => false,
        'is_primary' => true,
    ]);

    return $wali;
}

function midtransNotification(TopupWali $topup, string $status = 'settlement', string $serverKey = 'test-server-key'): array
{
    $statusCode = '200';
    $grossAmount = number_format($topup->nominal_diminta, 2, '.', '');

    return [
        'order_id' => $topup->midtrans_order_id,
        'status_code' => $statusCode,
        'gross_amount' => $grossAmount,
        'transaction_status' => $status,
        'transaction_id' => 'trx-'.$topup->id,
        'fraud_status' => 'accept',
        'signature_key' => hash('sha512', $topup->midtrans_order_id.$statusCode.$grossAmount.$serverKey),
    ];
}

beforeEach(function () {
    config(['services.midtrans.server_key' => 'test-server-key']);
});

it('always credits a plain top up fully to saldo, even with outstanding tagihan', function () {
    $santri = Santri::factory()->create();
    app(WalletService::class)->credit($santri, 150000, Transaksi::JENIS_TOPUP_TUNAI);

    $jenis = JenisTagihan::factory()->create(['nominal_default' => 100000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07');
    $tagihan = Tagihan::first();

    $topup = TopupWali::factory()->create([
        'santri_id' => $santri->id,
        'nominal_diminta' => 150000,
        'status' => TopupWali::STATUS_PENDING,
    ]);

    app(TopupWaliService::class)->handleWebhook(midtransNotification($topup));

    expect($tagihan->fresh()->status)->toBe(Tagihan::STATUS_BELUM_LUNAS)
        ->and($santri->saldo->fresh()->saldo)->toBe(300000)
        ->and($topup->fresh()->status)->toBe(TopupWali::STATUS_PAID)
        ->and($topup->fresh()->nominal_potongan_tagihan)->toBe(0)
        ->and($topup->fresh()->nominal_ke_saldo)->toBe(150000);
});

it('is idempotent when the same webhook notification is replayed', function () {
    $santri = Santri::factory()->create();
    $topup = TopupWali::factory()->create([
        'santri_id' => $santri->id,
        'nominal_diminta' => 75000,
        'status' => TopupWali::STATUS_PENDING,
    ]);

    $payload = midtransNotification($topup);
    $service = app(TopupWaliService::class);

    $service->handleWebhook($payload);
    $service->handleWebhook($payload);

    expect($santri->saldo->fresh()->saldo)->toBe(75000)
        ->and(Transaksi::where('santri_id', $santri->id)->count())->toBe(1);
});

it('rejects a webhook notification with a tampered signature and makes no changes', function () {
    $santri = Santri::factory()->create();
    $topup = TopupWali::factory()->create([
        'santri_id' => $santri->id,
        'nominal_diminta' => 75000,
        'status' => TopupWali::STATUS_PENDING,
    ]);

    $payload = midtransNotification($topup, 'settlement', 'wrong-server-key');

    expect(fn () => app(TopupWaliService::class)->handleWebhook($payload))
        ->toThrow(HttpException::class);

    expect($topup->fresh()->status)->toBe(TopupWali::STATUS_PENDING)
        ->and($santri->saldo->fresh()->saldo)->toBe(0);
});

// createSnapTransactionForTagihan()'s happy path makes a real Midtrans API
// call (same as the pre-existing createSnapTransaction(), which has never
// had direct test coverage for the same reason - no sandbox credentials in
// the test environment) - only its guard clauses, which run before any
// network call, are exercised here. The full create-to-settle round trip
// is what the "settles a tagihan-scoped top up..." test below covers, by
// seeding the TopupWali row directly rather than going through creation.

it('refuses to create a tagihan-scoped transaction for an already-lunas tagihan', function () {
    $wali = User::factory()->create();
    $santri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 50000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::first();

    app(TagihanService::class)->applyPembayaran($tagihan, 50000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);

    expect(fn () => app(TopupWaliService::class)->createSnapTransactionForTagihan($wali, $tagihan->fresh()))
        ->toThrow(InvalidArgumentException::class, 'sudah lunas');
});

it('refuses to create a second tagihan-scoped transaction while one is still pending', function () {
    $wali = User::factory()->create();
    $santri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 50000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::first();

    TopupWali::factory()->create([
        'santri_id' => $santri->id,
        'tagihan_id' => $tagihan->id,
        'status' => TopupWali::STATUS_PENDING,
    ]);

    expect(fn () => app(TopupWaliService::class)->createSnapTransactionForTagihan($wali, $tagihan))
        ->toThrow(InvalidArgumentException::class, 'masih diproses');
});

// createCoreApiTransactionForTagihan() shares its guard (already-lunas,
// pending-duplicate) with createSnapTransactionForTagihan() above via the
// same private guardTagihanUntukMidtrans() - this test only confirms the
// Core API entry point is actually wired to that shared guard, not a full
// re-test of the guard logic itself.
it('also refuses a core-api tagihan-scoped transaction for an already-lunas tagihan', function () {
    $wali = User::factory()->create();
    $santri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 50000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::first();

    app(TagihanService::class)->applyPembayaran($tagihan, 50000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);

    expect(fn () => app(TopupWaliService::class)->createCoreApiTransactionForTagihan($wali, $tagihan->fresh(), 'qris'))
        ->toThrow(InvalidArgumentException::class, 'sudah lunas');
});

it('settles a tagihan-scoped top up straight into the tagihan without touching saldo', function () {
    $santri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 90000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::first();

    $topup = TopupWali::factory()->create([
        'santri_id' => $santri->id,
        'tagihan_id' => $tagihan->id,
        'nominal_diminta' => 90000,
        'status' => TopupWali::STATUS_PENDING,
    ]);

    app(TopupWaliService::class)->handleWebhook(midtransNotification($topup));

    expect($tagihan->fresh()->status)->toBe(Tagihan::STATUS_LUNAS)
        ->and($santri->saldo?->saldo ?? 0)->toBe(0)
        ->and($topup->fresh()->nominal_potongan_tagihan)->toBe(90000)
        ->and($topup->fresh()->nominal_ke_saldo)->toBe(0)
        ->and(TagihanPembayaran::where('tagihan_id', $tagihan->id)->value('sumber'))
        ->toBe(TagihanPembayaran::SUMBER_TRANSFER_WALI_TAGIHAN);
});

it('routes a tagihan-scoped top up to saldo instead when the tagihan was already paid off elsewhere before the webhook arrives', function () {
    $wali = User::factory()->create();
    $santri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 70000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::first();

    $topup = TopupWali::factory()->create([
        'santri_id' => $santri->id,
        'tagihan_id' => $tagihan->id,
        'nominal_diminta' => 70000,
        'status' => TopupWali::STATUS_PENDING,
    ]);

    // Paid via cash at the counter in the meantime - Midtrans already
    // collected the 70000 by the time this webhook lands, so it can't
    // simply vanish; it should land in saldo instead.
    app(TagihanService::class)->applyPembayaran($tagihan, 70000, TagihanPembayaran::SUMBER_TUNAI_LANGSUNG);

    app(TopupWaliService::class)->handleWebhook(midtransNotification($topup));

    expect($topup->fresh()->nominal_potongan_tagihan)->toBe(0)
        ->and($topup->fresh()->nominal_ke_saldo)->toBe(70000)
        ->and($santri->saldo->fresh()->saldo)->toBe(70000);
});

it('is idempotent for a tagihan-scoped top up webhook replay', function () {
    $santri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 60000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::first();

    $topup = TopupWali::factory()->create([
        'santri_id' => $santri->id,
        'tagihan_id' => $tagihan->id,
        'nominal_diminta' => 60000,
        'status' => TopupWali::STATUS_PENDING,
    ]);

    $payload = midtransNotification($topup);
    $service = app(TopupWaliService::class);

    $service->handleWebhook($payload);
    $service->handleWebhook($payload);

    expect($tagihan->fresh()->nominal_terbayar)->toBe(60000)
        ->and(TagihanPembayaran::where('tagihan_id', $tagihan->id)->count())->toBe(1);
});

// chargeCoreApi() itself makes a real Midtrans API call and can't be tested
// end-to-end here (same reasoning as the Snap comment above) - these two
// tests instead seed a pending TopupWali with fee columns already set (as
// chargeCoreApi() would have left them) and confirm settle()/
// settleTagihanScoped() are completely indifferent to those columns: the
// santri is always credited the full nominal_diminta regardless of who
// bore the Midtrans fee or how much it was.

it('credits the santri in full via settle() regardless of a recorded wali-borne fee', function () {
    $santri = Santri::factory()->create();
    $topup = TopupWali::factory()->create([
        'santri_id' => $santri->id,
        'nominal_diminta' => 100000,
        'status' => TopupWali::STATUS_PENDING,
        'biaya_midtrans' => 4000,
        'biaya_ditanggung_wali' => true,
    ]);

    app(TopupWaliService::class)->handleWebhook(midtransNotification($topup));

    $transaksi = Transaksi::where('santri_id', $santri->id)->first();

    expect($santri->saldo->fresh()->saldo)->toBe(100000)
        ->and($topup->fresh()->nominal_ke_saldo)->toBe(100000)
        ->and($topup->fresh()->biaya_midtrans)->toBe(4000)
        ->and($topup->fresh()->biaya_ditanggung_wali)->toBeTrue()
        ->and(Kwitansi::where('topup_wali_id', $topup->id)->sole()->jenis)->toBe(Kwitansi::JENIS_TOPUP)
        // The fee has to reach the Transaksi row too, not just TopupWali -
        // TransaksiResource/TransaksiDetailScreen read it from here.
        ->and($transaksi->metadata['biaya_midtrans'])->toBe(4000)
        ->and($transaksi->metadata['biaya_ditanggung_wali'])->toBeTrue();
});

it('notifies every wali when a plain top up settles into saldo', function () {
    $santri = Santri::factory()->create();
    $wali = tambahWaliUntukTopup($santri);

    $topup = TopupWali::factory()->create([
        'santri_id' => $santri->id,
        'nominal_diminta' => 50000,
        'status' => TopupWali::STATUS_PENDING,
    ]);

    $push = $this->mock(PushNotificationService::class);
    $push->shouldReceive('notify')
        ->once()
        ->with(
            Mockery::on(fn ($u) => $u->is($wali)),
            'Top Up Berhasil',
            Mockery::type('string'),
            Mockery::on(fn ($data) => $data['type'] === 'topup_berhasil' && $data['santri_id'] === $santri->id),
        );

    app(TopupWaliService::class)->handleWebhook(midtransNotification($topup));
});

it('does not fire the saldo bertambah notification when a tagihan-scoped top up fully absorbs into the bill', function () {
    $santri = Santri::factory()->create();
    tambahWaliUntukTopup($santri);
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 90000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::first();

    $topup = TopupWali::factory()->create([
        'santri_id' => $santri->id,
        'tagihan_id' => $tagihan->id,
        'nominal_diminta' => 90000,
        'status' => TopupWali::STATUS_PENDING,
    ]);

    $push = $this->mock(PushNotificationService::class);
    $push->shouldNotReceive('notify');

    app(TopupWaliService::class)->handleWebhook(midtransNotification($topup));
});

it('preserves the tagihan-scoped invariant regardless of a recorded pondok-borne fee', function () {
    $santri = Santri::factory()->create();
    $jenis = JenisTagihan::factory()->create(['nominal_default' => 80000]);
    app(TagihanService::class)->generateTagihanForPeriode($jenis, '2026-07', null, null, null, [$santri->id]);
    $tagihan = Tagihan::first();

    $topup = TopupWali::factory()->create([
        'santri_id' => $santri->id,
        'tagihan_id' => $tagihan->id,
        'nominal_diminta' => 80000,
        'status' => TopupWali::STATUS_PENDING,
        'biaya_midtrans' => 3500,
        'biaya_ditanggung_wali' => false,
    ]);

    app(TopupWaliService::class)->handleWebhook(midtransNotification($topup));

    expect($tagihan->fresh()->status)->toBe(Tagihan::STATUS_LUNAS)
        ->and($topup->fresh()->nominal_potongan_tagihan)->toBe(80000)
        ->and($topup->fresh()->nominal_ke_saldo)->toBe(0)
        ->and($topup->fresh()->biaya_midtrans)->toBe(3500)
        ->and($topup->fresh()->biaya_ditanggung_wali)->toBeFalse();
});
