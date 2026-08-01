<?php

namespace App\Services;

use App\Exceptions\SaldoDiBawahMinimumException;
use App\Exceptions\TagihanTidakBisaDibatalkanException;
use App\Jobs\SendTagihanBaruNotifications;
use App\Models\JenisTagihan;
use App\Models\Santri;
use App\Models\Tagihan;
use App\Models\TagihanPembayaran;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TagihanService
{
    public function __construct(
        private WalletService $wallet,
        private SaldoFloorService $saldoFloor,
        private KwitansiService $kwitansi,
        private PushNotificationService $push,
    ) {}

    /**
     * Generate tagihan for the given periode. Idempotent: re-running for the
     * same jenis+periode only creates rows for santri that don't already have
     * one (unique(santri_id, jenis_tagihan_id, periode_label) guards this).
     *
     * By default every eligible (aktif, matching lembaga) santri gets a
     * tagihan. Pass $santriIds to restrict generation to a specific set of
     * santri instead - they are still required to be aktif and (if the jenis
     * is lembaga-scoped) belong to that lembaga, same as the "semua" path.
     */
    public function generateTagihanForPeriode(
        JenisTagihan $jenis,
        string $periodeLabel,
        ?Carbon $jatuhTempo = null,
        ?User $generatedBy = null,
        ?int $nominal = null,
        ?array $santriIds = null,
    ): array {
        $batchId = (string) Str::uuid();
        $dibuat = 0;
        $dilewati = 0;

        Santri::query()
            ->where('status', Santri::STATUS_AKTIF)
            ->when($jenis->lembaga_id, fn ($q) => $q->where('lembaga_id', $jenis->lembaga_id))
            ->when($santriIds !== null, fn ($q) => $q->whereIn('id', $santriIds))
            ->with('kategoriDiskon')
            ->chunkById(200, function ($santris) use ($jenis, $periodeLabel, $jatuhTempo, $generatedBy, $nominal, $batchId, &$dibuat, &$dilewati) {
                foreach ($santris as $santri) {
                    $nominalDasar = $nominal ?? $jenis->nominal_default;
                    $diskon = $this->hitungDiskon($jenis, $santri, $nominalDasar);

                    $tagihan = Tagihan::firstOrCreate(
                        [
                            'santri_id' => $santri->id,
                            'jenis_tagihan_id' => $jenis->id,
                            'periode_label' => $periodeLabel,
                        ],
                        [
                            'nominal' => $diskon['nominal'],
                            'nominal_sebelum_diskon' => $diskon['nominal_sebelum_diskon'],
                            'diskon_persen' => $diskon['diskon_persen'],
                            'kategori_diskon_id' => $diskon['kategori_diskon_id'],
                            'nominal_terbayar' => 0,
                            'status' => Tagihan::STATUS_BELUM_LUNAS,
                            'jatuh_tempo' => $jatuhTempo,
                            'generated_by' => $generatedBy?->id,
                            'generated_batch_id' => $batchId,
                        ]
                    );

                    $tagihan->wasRecentlyCreated ? $dibuat++ : $dilewati++;
                }
            });

        if ($dibuat > 0) {
            SendTagihanBaruNotifications::dispatch($batchId);
        }

        return ['batch_id' => $batchId, 'dibuat' => $dibuat, 'dilewati' => $dilewati];
    }

    /**
     * Discount is only applied when the jenis honors it AND the santri has an
     * active kategori - the percentage and pre-discount amount are returned
     * so the caller can snapshot them onto the tagihan row itself.
     *
     * @return array{nominal: int, nominal_sebelum_diskon: ?int, diskon_persen: ?int, kategori_diskon_id: ?int}
     */
    private function hitungDiskon(JenisTagihan $jenis, Santri $santri, int $nominalDasar): array
    {
        $kategori = $santri->kategoriDiskon;

        if (! $jenis->berlaku_diskon || ! $kategori || ! $kategori->is_active) {
            return [
                'nominal' => $nominalDasar,
                'nominal_sebelum_diskon' => null,
                'diskon_persen' => null,
                'kategori_diskon_id' => null,
            ];
        }

        $nominalSetelahDiskon = (int) round($nominalDasar * (1 - $kategori->persentase / 100));

        return [
            'nominal' => $nominalSetelahDiskon,
            'nominal_sebelum_diskon' => $nominalDasar,
            'diskon_persen' => $kategori->persentase,
            'kategori_diskon_id' => $kategori->id,
        ];
    }

    /**
     * Apply a payment against a tagihan, clamped to the remaining balance.
     */
    public function applyPembayaran(Tagihan $tagihan, int $nominal, string $sumber, array $attrs = []): TagihanPembayaran
    {
        if ($nominal <= 0) {
            throw new InvalidArgumentException('Nominal pembayaran harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($tagihan, $nominal, $sumber, $attrs) {
            /** @var Tagihan $locked */
            $locked = Tagihan::query()->lockForUpdate()->findOrFail($tagihan->id);

            // The real guard against paying a cancelled tagihan - every
            // payment entry point (admin cash, wali saldo, Midtrans webhook)
            // funnels through here, so this is the one place that has to
            // enforce it rather than trusting each caller's own UI to hide
            // the right button.
            if ($locked->status === Tagihan::STATUS_DIBATALKAN) {
                throw new InvalidArgumentException('Tagihan ini sudah dibatalkan dan tidak bisa dibayar.');
            }

            $nominalDiterapkan = min($nominal, $locked->sisa());

            $pembayaran = TagihanPembayaran::create(array_merge([
                'tagihan_id' => $locked->id,
                'nominal' => $nominalDiterapkan,
                'sumber' => $sumber,
                'dibayar_at' => now(),
            ], $attrs));

            $terbayar = $locked->nominal_terbayar + $nominalDiterapkan;

            $locked->update([
                'nominal_terbayar' => $terbayar,
                'status' => $terbayar >= $locked->nominal ? Tagihan::STATUS_LUNAS : Tagihan::STATUS_SEBAGIAN,
            ]);

            // Every payment source (saldo, tunai_langsung, transfer_wali_tagihan)
            // funnels through here, so this is the single place that issues
            // a kwitansi resmi for tagihan payments - see KwitansiService.
            $this->kwitansi->terbitkanUntukTagihan($pembayaran);

            if ($sumber === TagihanPembayaran::SUMBER_TUNAI_LANGSUNG) {
                DB::afterCommit(fn () => $this->notifyPembayaranTunai($locked, $pembayaran));
            }

            return $pembayaran;
        });
    }

    private function notifyPembayaranTunai(Tagihan $tagihan, TagihanPembayaran $pembayaran): void
    {
        $santri = $tagihan->santri;
        $status = $tagihan->fresh()->status === Tagihan::STATUS_LUNAS ? 'lunas' : 'terbayar sebagian';
        $body = 'Pembayaran tunai tagihan '.$tagihan->jenisTagihan->nama.' untuk '
            .$santri->nama.' sebesar Rp'.number_format((int) $pembayaran->nominal, 0, ',', '.')
            ." berhasil dicatat. Status tagihan: {$status}.";

        foreach ($santri->walis as $wali) {
            $this->push->notify($wali, 'Pembayaran Tagihan Berhasil', $body, [
                'type' => 'pembayaran_tagihan_tunai',
                'santri_id' => $santri->id,
                'santri_nama' => $santri->nama,
                'tagihan_id' => $tagihan->id,
                'pembayaran_id' => $pembayaran->id,
                'status_tagihan' => $status,
            ]);
        }
    }

    /**
     * Pay a tagihan directly out of the santri's existing saldo (used by both
     * the wali web portal and the wali API). Locks the tagihan first so the
     * debited amount and the applied payment are always derived from the
     * same up-to-date "sisa", even under concurrent payment attempts.
     * Returns null if the tagihan is already fully paid.
     *
     * $nominal is optional - null (or omitted) pays the full remaining sisa,
     * same as before this parameter existed. A smaller amount is only
     * accepted when the tagihan's JenisTagihan has bisa_dicicil enabled -
     * not every jenis tagihan is meant to be payable in installments (e.g.
     * a one-off registration fee), so this is opt-in per jenis rather than
     * a blanket capability. Rejected explicitly rather than silently
     * clamped up to the full amount, since silently charging more than the
     * wali asked for would be a real trust problem.
     *
     * Refuses to drop saldo below SaldoFloorService::minimal() - a wali who
     * hits this should use TopupWaliService::createSnapTransactionForTagihan()
     * instead, which settles the tagihan without touching saldo at all.
     *
     * @throws SaldoDiBawahMinimumException
     * @throws InvalidArgumentException
     */
    public function bayarDariSaldo(Tagihan $tagihan, User $diprosesOleh, ?int $nominal = null, ?string $requestId = null): ?TagihanPembayaran
    {
        return DB::transaction(function () use ($tagihan, $diprosesOleh, $nominal, $requestId) {
            $locked = Tagihan::query()->lockForUpdate()->findOrFail($tagihan->id);

            if ($requestId !== null) {
                $existing = Transaksi::query()
                    ->where('idempotency_key', $requestId)
                    ->where('santri_id', $locked->santri_id)
                    ->where('tagihan_id', $locked->id)
                    ->first();

                if ($existing) {
                    return TagihanPembayaran::query()
                        ->where('transaksi_id', $existing->id)
                        ->first();
                }
            }

            $sisa = $locked->sisa();

            if ($sisa <= 0) {
                return null;
            }

            $nominalDibayar = $nominal ?? $sisa;

            if ($nominalDibayar <= 0) {
                throw new InvalidArgumentException('Nominal pembayaran harus lebih besar dari 0.');
            }

            if ($nominalDibayar > $sisa) {
                throw new InvalidArgumentException('Nominal melebihi sisa tagihan.');
            }

            if ($nominalDibayar < $sisa && ! $locked->jenisTagihan->bisa_dicicil) {
                throw new InvalidArgumentException('Tagihan ini tidak bisa dicicil dan harus dibayar penuh sekaligus.');
            }

            // Locked here (same outer transaction) so the floor check reads
            // an up-to-date balance, not a stale one two concurrent payments
            // could each individually pass against. Only checked when saldo
            // can cover the payment at all - otherwise debit() below throws
            // its own InsufficientBalanceException, which must win over the
            // floor message here (truly not enough money, vs enough but
            // policy-blocked, are different facts the wali needs to see).
            $saldo = $this->wallet->lockSaldo($locked->santri);
            $minimal = $this->saldoFloor->minimal();

            if ($saldo->saldo >= $nominalDibayar && $saldo->saldo - $nominalDibayar < $minimal) {
                throw new SaldoDiBawahMinimumException(
                    'Saldo tidak bisa dipakai untuk membayar tagihan ini karena akan membuat saldo santri di bawah batas minimum Rp '
                    .number_format($minimal, 0, ',', '.')
                    .'. Gunakan opsi "Bayar Langsung via Midtrans" untuk melunasi tagihan ini tanpa mengurangi saldo.'
                );
            }

            $transaksi = $this->wallet->debit($locked->santri, $nominalDibayar, Transaksi::JENIS_PEMBAYARAN_TAGIHAN, [
                'metode' => Transaksi::METODE_SISTEM,
                'tagihan_id' => $locked->id,
                'diproses_oleh' => $diprosesOleh->id,
                'idempotency_key' => $requestId,
            ]);

            return $this->applyPembayaran($locked, $nominalDibayar, TagihanPembayaran::SUMBER_SALDO, [
                'transaksi_id' => $transaksi->id,
                'dicatat_oleh' => $diprosesOleh->id,
            ]);
        });
    }

    /**
     * Null when it's safe to cancel the tagihan; otherwise a human-readable
     * reason it can't be cancelled yet. Deliberately scoped to tagihan with
     * zero nominal_terbayar - once any money has moved (saldo debit, cash,
     * or a Midtrans payment), cancelling isn't a pure metadata change
     * anymore since there's no reversal/refund primitive in this system,
     * and building one is a separate, much more involved decision than a
     * "cancel a mistaken tagihan" button should make on someone's behalf.
     */
    public function alasanTidakBisaDibatalkan(Tagihan $tagihan): ?string
    {
        if ($tagihan->status === Tagihan::STATUS_DIBATALKAN) {
            return 'Tagihan ini sudah dibatalkan sebelumnya.';
        }

        if ($tagihan->nominal_terbayar > 0) {
            return 'Tagihan ini sudah memiliki pembayaran (Rp '.number_format($tagihan->nominal_terbayar, 0, ',', '.')
                .'). Pembatalan hanya bisa dilakukan untuk tagihan yang belum ada pembayarannya sama sekali - hubungi bendahara untuk koreksi tagihan yang sudah dibayar.';
        }

        return null;
    }

    /**
     * @throws TagihanTidakBisaDibatalkanException
     */
    public function batalkan(Tagihan $tagihan, User $dibatalkanOleh, string $alasan): void
    {
        DB::transaction(function () use ($tagihan, $dibatalkanOleh, $alasan) {
            /** @var Tagihan $locked */
            $locked = Tagihan::query()->lockForUpdate()->findOrFail($tagihan->id);

            if ($blokir = $this->alasanTidakBisaDibatalkan($locked)) {
                throw new TagihanTidakBisaDibatalkanException($blokir);
            }

            $locked->update([
                'status' => Tagihan::STATUS_DIBATALKAN,
                'alasan_pembatalan' => $alasan,
                'dibatalkan_oleh' => $dibatalkanOleh->id,
                'dibatalkan_at' => now(),
            ]);
        });

        activity('tagihan')
            ->causedBy($dibatalkanOleh)
            ->performedOn($tagihan)
            ->withProperties(['alasan' => $alasan])
            ->log('Membatalkan tagihan');
    }
}
