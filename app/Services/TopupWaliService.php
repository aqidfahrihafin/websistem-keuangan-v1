<?php

namespace App\Services;

use App\Exceptions\MidtransNotConfiguredException;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\TagihanPembayaran;
use App\Models\TopupWali;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Midtrans\Config;
use Midtrans\CoreApi;
use Midtrans\Snap;
use Midtrans\Transaction;

class TopupWaliService
{
    public const METODE_BNI_VA = 'bni_va';

    public const METODE_BCA_VA = 'bca_va';

    public const METODE_BRI_VA = 'bri_va';

    public const METODE_QRIS = 'qris';

    // Public - also read by TopupWali::metodeKode() to resolve the same
    // specific-channel label for display (admin top up list, mobile
    // receipts) without duplicating this map in two places.
    public const BANK_VA_METODE = [
        self::METODE_BNI_VA => 'bni',
        self::METODE_BCA_VA => 'bca',
        self::METODE_BRI_VA => 'bri',
    ];

    public function __construct(
        private WalletService $wallet,
        private TagihanService $tagihanService,
        private MidtransSettingsService $midtransSettings,
        private MidtransFeeService $feeService,
        private PushNotificationService $push,
        private KwitansiService $kwitansi,
    ) {
        Config::$serverKey = (string) $this->midtransSettings->serverKey();
        Config::$isProduction = $this->midtransSettings->isProduction();
        Config::$isSanitized = true;
        Config::$is3ds = true;

        // Many local PHP installs (esp. Windows/XAMPP/Laragon) have no
        // curl.cainfo configured, which makes every outbound HTTPS request
        // fail with "unable to get local issuer certificate". Point cURL at
        // the CA bundle midtrans-php already ships in its own package so
        // this works out of the box regardless of the host's php.ini.
        $bundledCaBundle = base_path('vendor/midtrans/midtrans-php/data/cacert.pem');

        if (is_file($bundledCaBundle)) {
            Config::$curlOptions[CURLOPT_CAINFO] = $bundledCaBundle;

            // ApiRequestor::createOrUpdateSubscription/createTransaction reads
            // Config::$curlOptions[CURLOPT_HTTPHEADER] directly (no isset
            // check) as soon as Config::$curlOptions is non-empty, so setting
            // CAINFO alone triggers an "undefined array key" warning. Seed an
            // empty array so that read is safe - array_replace_recursive
            // treats an empty override array as "no changes", so the real
            // headers built later are left intact.
            Config::$curlOptions[CURLOPT_HTTPHEADER] = [];
        }
    }

    public function createSnapTransaction(User $wali, Santri $santri, int $nominal): TopupWali
    {
        if ($nominal <= 0) {
            throw new InvalidArgumentException('Nominal top-up harus lebih besar dari 0.');
        }

        if (! $this->midtransSettings->isConfigured()) {
            throw new MidtransNotConfiguredException('Midtrans belum dikonfigurasi oleh admin pondok.');
        }

        $orderId = 'TOPUP-'.$santri->id.'-'.now()->format('YmdHis').'-'.Str::random(6);

        $topup = TopupWali::create([
            'user_id' => $wali->id,
            'santri_id' => $santri->id,
            'nominal_diminta' => $nominal,
            'midtrans_order_id' => $orderId,
            'status' => TopupWali::STATUS_PENDING,
        ]);

        $transaction = Snap::createTransaction([
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $nominal,
            ],
            'customer_details' => $this->customerDetails($wali),
        ]);

        $topup->update([
            'snap_token' => $transaction->token,
            'redirect_url' => $transaction->redirect_url,
        ]);

        return $topup->fresh();
    }

    /**
     * Creates a Snap transaction scoped to a single tagihan, for exactly its
     * remaining amount - settled straight into that bill on payment,
     * bypassing saldo entirely (see settle()). Order-id is prefixed
     * TAGIHAN- (vs TOPUP- for a plain top-up) purely so the two flows are
     * distinguishable at a glance in the Midtrans merchant dashboard.
     */
    public function createSnapTransactionForTagihan(User $wali, Tagihan $tagihan): TopupWali
    {
        $orderId = 'TAGIHAN-'.$tagihan->id.'-'.now()->format('YmdHis').'-'.Str::random(6);

        // Locked for the whole reservation - the guard check and the
        // TopupWali::create() below both happen before this releases, so
        // a second concurrent request for the same tagihan (a double-tap,
        // or a client retrying after a slow response) can't pass the
        // "no pending transaction yet" check before this request's row
        // actually exists. Holding the lock across the Midtrans API call
        // too is an acceptable trade-off here (unlike WalletService's
        // saldo lock, which is hit constantly) - two people racing to pay
        // the exact same tagihan via Midtrans at the same instant is rare.
        $topup = DB::transaction(function () use ($wali, $tagihan, $orderId) {
            // Same error-priority order as before this method held a lock
            // at all: tagihan-state problems (already lunas / already has
            // a pending Midtrans transaction) are checked before "Midtrans
            // isn't configured" - a wali should hear the more specific,
            // actionable reason first.
            $sisa = $this->guardTagihanUntukMidtrans($tagihan, lock: true);

            if (! $this->midtransSettings->isConfigured()) {
                throw new MidtransNotConfiguredException('Midtrans belum dikonfigurasi oleh admin pondok.');
            }

            $topup = TopupWali::create([
                'user_id' => $wali->id,
                'santri_id' => $tagihan->santri_id,
                'tagihan_id' => $tagihan->id,
                'nominal_diminta' => $sisa,
                'midtrans_order_id' => $orderId,
                'status' => TopupWali::STATUS_PENDING,
            ]);

            $transaction = Snap::createTransaction([
                'transaction_details' => [
                    'order_id' => $orderId,
                    'gross_amount' => $sisa,
                ],
                'customer_details' => $this->customerDetails($wali),
            ]);

            $topup->update([
                'snap_token' => $transaction->token,
                'redirect_url' => $transaction->redirect_url,
            ]);

            return $topup;
        });

        return $topup->fresh();
    }

    /** @return array{first_name: string, email: ?string, phone: ?string} */
    private function customerDetails(User $wali): array
    {
        return [
            'first_name' => $wali->name,
            'email' => $wali->email,
            'phone' => $wali->phone,
        ];
    }

    /**
     * Shared by createSnapTransactionForTagihan() and
     * createCoreApiTransactionForTagihan() - returns the tagihan's current
     * sisa() once it's confirmed payable via a fresh Midtrans transaction.
     */
    private function guardTagihanUntukMidtrans(Tagihan $tagihan, bool $lock = false): int
    {
        if ($lock) {
            // Callers wrap this in DB::transaction() and hold the lock
            // through their own TopupWali::create() call too - locking
            // only inside this method wouldn't help, since the lock would
            // release the moment this returns, before the row that's
            // supposed to make a second check fail even exists yet.
            $tagihan = Tagihan::whereKey($tagihan->id)->lockForUpdate()->firstOrFail();
        }

        $sisa = $tagihan->sisa();

        if ($sisa <= 0) {
            throw new InvalidArgumentException('Tagihan ini sudah lunas.');
        }

        if (TopupWali::where('tagihan_id', $tagihan->id)->where('status', TopupWali::STATUS_PENDING)->exists()) {
            throw new InvalidArgumentException('Sudah ada pembayaran Midtrans yang masih diproses untuk tagihan ini.');
        }

        return $sisa;
    }

    /**
     * Charges directly via Midtrans's Core API instead of Snap, for a wali
     * building their own payment UI instead of using Midtrans's hosted Snap
     * page. VA BNI/BCA/BRI and QRIS are wired up - all return data the
     * client can render itself (a VA number to display, or a QR image URL
     * to show), unlike Snap's redirect_url which requires an external
     * browser. handleWebhook()/syncStatusFromMidtrans() work unchanged for
     * these transactions since Midtrans notifies/reports status the same
     * way regardless of how the charge was created.
     */
    public function createCoreApiTransaction(User $wali, Santri $santri, int $nominal, string $metode): TopupWali
    {
        if ($nominal <= 0) {
            throw new InvalidArgumentException('Nominal top-up harus lebih besar dari 0.');
        }

        $orderId = 'TOPUP-'.$santri->id.'-'.now()->format('YmdHis').'-'.Str::random(6);
        $attributes = $this->chargeCoreApi($wali, $nominal, $metode, $orderId, untukTagihan: false);

        // fresh() re-reads the row so nominal_potongan_tagihan/nominal_ke_saldo
        // come back as the DB's default 0 instead of null - create() only
        // returns the in-memory model with the attributes it was given, and
        // those two are deliberately omitted here (they're filled in later
        // by settle()), same as createSnapTransaction() above.
        return TopupWali::create(array_merge($attributes, [
            'user_id' => $wali->id,
            'santri_id' => $santri->id,
            'nominal_diminta' => $nominal,
            'midtrans_order_id' => $orderId,
        ]))->fresh();
    }

    /**
     * Core API counterpart to createSnapTransactionForTagihan() - charges
     * exactly the tagihan's remaining amount via VA/QRIS instead of a Snap
     * redirect, for a wali building their own payment UI (this is what the
     * mobile app uses, since it has no Snap/WebView support). Settled
     * straight into that bill without touching saldo, same as the Snap path.
     */
    public function createCoreApiTransactionForTagihan(User $wali, Tagihan $tagihan, string $metode): TopupWali
    {
        $orderId = 'TAGIHAN-'.$tagihan->id.'-'.now()->format('YmdHis').'-'.Str::random(6);

        // Same lock-for-the-whole-reservation reasoning as
        // createSnapTransactionForTagihan() - see the comment there.
        $topup = DB::transaction(function () use ($wali, $tagihan, $metode, $orderId) {
            $sisa = $this->guardTagihanUntukMidtrans($tagihan, lock: true);
            $attributes = $this->chargeCoreApi($wali, $sisa, $metode, $orderId, untukTagihan: true);

            return TopupWali::create(array_merge($attributes, [
                'user_id' => $wali->id,
                'santri_id' => $tagihan->santri_id,
                'tagihan_id' => $tagihan->id,
                'nominal_diminta' => $sisa,
                'midtrans_order_id' => $orderId,
            ]));
        });

        return $topup->fresh();
    }

    /**
     * Shared by createCoreApiTransaction() and
     * createCoreApiTransactionForTagihan() - validates the metode, charges
     * via Midtrans's Core API, and returns everything about the result
     * except the identity fields (user_id/santri_id/tagihan_id/nominal_diminta/
     * midtrans_order_id), which differ per caller and are merged in there.
     *
     * @return array<string, mixed>
     */
    private function chargeCoreApi(User $wali, int $nominal, string $metode, string $orderId, bool $untukTagihan): array
    {
        $bank = self::BANK_VA_METODE[$metode] ?? null;
        $isVa = $bank !== null;

        if (! $isVa && $metode !== self::METODE_QRIS) {
            throw new InvalidArgumentException("Metode top-up '{$metode}' tidak didukung.");
        }

        if (! $this->midtransSettings->isConfigured()) {
            throw new MidtransNotConfiguredException('Midtrans belum dikonfigurasi oleh admin pondok.');
        }

        // Locked in now, not re-derived later - so toggling the fee setting
        // after this charge never retroactively changes what this specific
        // transaction is recorded as having cost.
        $biaya = $this->feeService->hitungBiaya($metode, $nominal);
        $dibebankanWali = $this->feeService->dibebankanWali($untukTagihan);
        $grossAmount = $dibebankanWali ? $nominal + $biaya : $nominal;

        $params = [
            'payment_type' => $isVa ? 'bank_transfer' : 'qris',
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => $this->customerDetails($wali),
        ];

        if ($isVa) {
            $params['bank_transfer'] = ['bank' => $bank];
        }

        $response = json_decode(json_encode(CoreApi::charge($params)), true);

        $attributes = [
            'midtrans_transaction_id' => $response['transaction_id'] ?? null,
            'status' => TopupWali::STATUS_PENDING,
            'payment_type' => $metode,
            'raw_notification' => $response,
            'expiry_time' => $response['expiry_time'] ?? null,
            'biaya_midtrans' => $biaya,
            'biaya_ditanggung_wali' => $dibebankanWali,
        ];

        if ($isVa) {
            $vaNumber = $response['va_numbers'][0] ?? null;
            $attributes['va_bank'] = $vaNumber['bank'] ?? null;
            $attributes['va_number'] = $vaNumber['va_number'] ?? null;
        } else {
            $qrAction = collect($response['actions'] ?? [])->firstWhere('name', 'generate-qr-code');
            $attributes['qr_url'] = $qrAction['url'] ?? null;
        }

        return $attributes;
    }

    /**
     * Idempotent Midtrans notification handler: replays of the same order_id
     * are safe because a topup already in a terminal status short-circuits,
     * and the eventual saldo credit reuses the order_id as idempotency_key.
     */
    public function handleWebhook(array $payload): TopupWali
    {
        $this->verifySignature($payload);

        $orderId = $payload['order_id'] ?? null;
        abort_unless($orderId, 422, 'order_id tidak ditemukan pada notifikasi.');

        return DB::transaction(function () use ($payload, $orderId) {
            /** @var TopupWali $topup */
            $topup = TopupWali::query()->lockForUpdate()->where('midtrans_order_id', $orderId)->firstOrFail();

            $terminalStatuses = [
                TopupWali::STATUS_PAID,
                TopupWali::STATUS_FAILED,
                TopupWali::STATUS_EXPIRED,
                TopupWali::STATUS_CANCELLED,
                TopupWali::STATUS_REFUNDED,
            ];

            if (in_array($topup->status, $terminalStatuses, true)) {
                return $topup;
            }

            $transactionStatus = $payload['transaction_status'] ?? null;
            $fraudStatus = $payload['fraud_status'] ?? null;

            // Core API already knows payment_type/va_bank at creation time
            // (chargeCoreApi() sets them straight away), but a Snap top up
            // doesn't - the wali picks the method on Midtrans's own hosted
            // page, so this webhook notification is the first (and only)
            // place that information ever appears. Falls back to whatever
            // was already there (not null) so a later webhook retry with a
            // payload missing these keys can't blank out a value Core API
            // already set correctly.
            $topup->update([
                'midtrans_transaction_id' => $payload['transaction_id'] ?? null,
                'raw_notification' => $payload,
                'payment_type' => $payload['payment_type'] ?? $topup->payment_type,
                'va_bank' => $payload['va_numbers'][0]['bank'] ?? $topup->va_bank,
            ]);

            $isSuccess = in_array($transactionStatus, ['capture', 'settlement'], true)
                && ($fraudStatus === null || $fraudStatus === 'accept');

            if ($isSuccess) {
                $this->settle($topup);
                DB::afterCommit(fn () => $this->notifySaldoBertambah($topup));
            } elseif (in_array($transactionStatus, ['deny', 'cancel'], true)) {
                $topup->update(['status' => TopupWali::STATUS_CANCELLED]);
            } elseif ($transactionStatus === 'expire') {
                $topup->update(['status' => TopupWali::STATUS_EXPIRED]);
            } elseif ($transactionStatus === 'refund') {
                $topup->update(['status' => TopupWali::STATUS_REFUNDED]);
            }

            return $topup->fresh();
        });
    }

    /**
     * Pulls the current status directly from Midtrans and feeds it through
     * the same idempotent handleWebhook() path. Exists because a webhook
     * notification is a push from Midtrans's servers to ours - it can never
     * reach a machine that isn't publicly reachable (e.g. local development
     * behind NAT/localhost with no tunnel), and even in production, webhook
     * delivery is not 100% guaranteed. This lets a stuck "pending" topup be
     * reconciled on demand instead of waiting on the webhook forever.
     */
    public function syncStatusFromMidtrans(TopupWali $topup): TopupWali
    {
        $status = Transaction::status($topup->midtrans_order_id);
        $payload = json_decode(json_encode($status), true);

        return $this->handleWebhook($payload);
    }

    /**
     * A plain top-up (tagihan_id null) always credits 100% to saldo - no
     * auto-deduction for outstanding tagihan. A tagihan-scoped top-up
     * (created via createSnapTransactionForTagihan()) instead pays that one
     * tagihan directly and never touches saldo, except for any leftover if
     * the tagihan could no longer absorb the full amount (see
     * settleTagihanScoped()).
     */
    private function settle(TopupWali $topup): void
    {
        if ($topup->tagihan_id) {
            $this->settleTagihanScoped($topup);

            return;
        }

        $transaksi = $this->wallet->credit($topup->santri, $topup->nominal_diminta, Transaksi::JENIS_TOPUP_TRANSFER_WALI, [
            'metode' => Transaksi::METODE_MIDTRANS,
            'idempotency_key' => $topup->midtrans_order_id,
            'external_reference' => $topup->midtrans_transaction_id,
            'metadata' => [
                'metode_detail' => $topup->metodeKode(),
                'biaya_midtrans' => $topup->biaya_midtrans,
                'biaya_ditanggung_wali' => $topup->biaya_ditanggung_wali,
            ],
        ]);

        $topup->update([
            'status' => TopupWali::STATUS_PAID,
            'nominal_potongan_tagihan' => 0,
            'nominal_ke_saldo' => $topup->nominal_diminta,
            'paid_at' => now(),
        ]);

        $this->kwitansi->terbitkanUntukTopup($topup, $transaksi);
    }

    /**
     * Invariant: nominal_potongan_tagihan + nominal_ke_saldo === nominal_diminta,
     * always - money is never lost, only redirected to saldo when the
     * tagihan can no longer absorb it (e.g. it was already paid off through
     * another channel - cash, saldo - between the Midtrans transaction being
     * created and this webhook arriving; Midtrans already collected the
     * money by then, so it can't simply be un-collected).
     */
    private function settleTagihanScoped(TopupWali $topup): void
    {
        $sisaSaatIni = $topup->tagihan->fresh()->sisa();
        $potongan = 0;

        if ($sisaSaatIni > 0) {
            // applyPembayaran re-locks and clamps against the live "sisa",
            // so reconcile against what it actually applied rather than the
            // full requested amount.
            $pembayaran = $this->tagihanService->applyPembayaran($topup->tagihan, $topup->nominal_diminta, TagihanPembayaran::SUMBER_TRANSFER_WALI_TAGIHAN, [
                'topup_wali_id' => $topup->id,
                'dicatat_oleh' => $topup->user_id,
            ]);

            $potongan = $pembayaran->nominal;
        }

        $keSaldo = $topup->nominal_diminta - $potongan;

        if ($keSaldo > 0) {
            $this->wallet->credit($topup->santri, $keSaldo, Transaksi::JENIS_TOPUP_TRANSFER_WALI, [
                'metode' => Transaksi::METODE_MIDTRANS,
                'idempotency_key' => $topup->midtrans_order_id,
                'external_reference' => $topup->midtrans_transaction_id,
                'metadata' => [
                    'metode_detail' => $topup->metodeKode(),
                    'biaya_midtrans' => $topup->biaya_midtrans,
                    'biaya_ditanggung_wali' => $topup->biaya_ditanggung_wali,
                ],
            ]);
        }

        $topup->update([
            'status' => TopupWali::STATUS_PAID,
            'nominal_potongan_tagihan' => $potongan,
            'nominal_ke_saldo' => $keSaldo,
            'paid_at' => now(),
        ]);
    }

    /**
     * Only fires when nominal_ke_saldo > 0 - a tagihan-scoped topup that
     * fully absorbed into the bill (no leftover) is a "tagihan lunas" event,
     * not a "saldo bertambah" one, and isn't notified here at all.
     */
    private function notifySaldoBertambah(TopupWali $topup): void
    {
        if ($topup->nominal_ke_saldo <= 0) {
            return;
        }

        $santri = $topup->santri;
        // Loaded fresh here (not cached from earlier in the request), so
        // this reflects the balance after settle()'s credit() already
        // committed, not a stale pre-topup snapshot.
        $saldoAkhir = $santri->saldo?->saldo ?? 0;

        $body = "Saldo {$santri->nama} bertambah Rp".number_format($topup->nominal_ke_saldo, 0, ',', '.').'. '
            .'Saldo akhir: Rp'.number_format($saldoAkhir, 0, ',', '.').'.';

        $transaksiId = Transaksi::query()
            ->where('santri_id', $santri->id)
            ->where('idempotency_key', $topup->midtrans_order_id)
            ->value('id');

        foreach ($santri->walis as $wali) {
            $this->push->notify($wali, 'Top Up Berhasil', $body, [
                'type' => 'topup_berhasil',
                'santri_id' => $santri->id,
                'santri_nama' => $santri->nama,
                'topup_id' => $topup->id,
                'transaksi_id' => $transaksiId,
                'saldo_akhir' => $saldoAkhir,
            ]);
        }
    }

    private function verifySignature(array $payload): void
    {
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        $expected = hash('sha512', $orderId.$statusCode.$grossAmount.$this->midtransSettings->serverKey());

        abort_unless(hash_equals($expected, (string) $signatureKey), 403, 'Signature midtrans tidak valid.');
    }
}
