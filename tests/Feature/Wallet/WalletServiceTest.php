<?php

use App\Exceptions\InsufficientBalanceException;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Models\User;
use App\Models\WaliSantri;
use App\Services\PushNotificationService;
use App\Services\WalletService;

function tambahWaliUntukWallet(Santri $santri): User
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

function makeSantriWithSaldo(int $saldo = 0): Santri
{
    $santri = Santri::factory()->create();

    if ($saldo > 0) {
        app(WalletService::class)->credit($santri, $saldo, Transaksi::JENIS_TOPUP_TUNAI);
    }

    return $santri;
}

it('credits a santri wallet and records an accurate ledger snapshot', function () {
    $santri = Santri::factory()->create();
    $wallet = app(WalletService::class);

    $tx = $wallet->credit($santri, 50000, Transaksi::JENIS_TOPUP_TUNAI);

    expect($tx->saldo_sebelum)->toBe(0)
        ->and($tx->saldo_sesudah)->toBe(50000)
        ->and($tx->arah)->toBe(Transaksi::ARAH_KREDIT)
        ->and($santri->saldo->fresh()->saldo)->toBe(50000);
});

it('notifies wali when a cash-session deposit credits the santri wallet', function () {
    $santri = Santri::factory()->create();
    $wali = tambahWaliUntukWallet($santri);

    $push = $this->mock(PushNotificationService::class);
    $push->shouldReceive('notify')
        ->once()
        ->with(
            Mockery::on(fn ($u) => $u->is($wali)),
            'Setoran Saldo Berhasil',
            Mockery::type('string'),
            Mockery::on(fn ($data) => $data['type'] === 'setoran_saldo_tunai'
                && $data['santri_id'] === $santri->id
                && isset($data['transaksi_id'])),
        );

    app(WalletService::class)->credit($santri, 50000, Transaksi::JENIS_TOPUP_TUNAI, [
        'metode' => Transaksi::METODE_TUNAI,
        'metadata' => ['sesi_kas_id' => 10],
    ]);
});

it('does not duplicate notifications for cash topups outside a cash session', function () {
    $santri = Santri::factory()->create();
    tambahWaliUntukWallet($santri);

    $push = $this->mock(PushNotificationService::class);
    $push->shouldNotReceive('notify');

    app(WalletService::class)->credit($santri, 50000, Transaksi::JENIS_TOPUP_TUNAI);
});

it('debits a santri wallet and records an accurate ledger snapshot', function () {
    $santri = makeSantriWithSaldo(50000);
    $wallet = app(WalletService::class);

    $tx = $wallet->debit($santri, 20000, Transaksi::JENIS_PEMBAYARAN_TAGIHAN);

    expect($tx->saldo_sebelum)->toBe(50000)
        ->and($tx->saldo_sesudah)->toBe(30000)
        ->and($santri->saldo->fresh()->saldo)->toBe(30000);
});

it('refuses to debit beyond the available balance and leaves no side effects', function () {
    $santri = makeSantriWithSaldo(10000);
    $wallet = app(WalletService::class);

    expect(fn () => $wallet->debit($santri, 20000, Transaksi::JENIS_PEMBAYARAN_TAGIHAN))
        ->toThrow(InsufficientBalanceException::class);

    expect($santri->saldo->fresh()->saldo)->toBe(10000)
        ->and(Transaksi::where('santri_id', $santri->id)->count())->toBe(1); // only the initial topup
});

it('notifies every wali of the santri once a debit commits', function () {
    $santri = makeSantriWithSaldo(50000);
    $wali = tambahWaliUntukWallet($santri);

    $push = $this->mock(PushNotificationService::class);
    $push->shouldReceive('notify')
        ->once()
        ->with(
            Mockery::on(fn ($u) => $u->is($wali)),
            'Pembayaran Tagihan',
            Mockery::type('string'),
            Mockery::on(fn ($data) => $data['type'] === Transaksi::JENIS_PEMBAYARAN_TAGIHAN && $data['santri_id'] === $santri->id),
        );

    app(WalletService::class)->debit($santri, 20000, Transaksi::JENIS_PEMBAYARAN_TAGIHAN);
});

it('does not notify when a debit fails due to insufficient balance', function () {
    $santri = makeSantriWithSaldo(10000);
    tambahWaliUntukWallet($santri);

    $push = $this->mock(PushNotificationService::class);
    $push->shouldNotReceive('notify');

    expect(fn () => app(WalletService::class)->debit($santri, 20000, Transaksi::JENIS_PEMBAYARAN_TAGIHAN))
        ->toThrow(InsufficientBalanceException::class);
});
