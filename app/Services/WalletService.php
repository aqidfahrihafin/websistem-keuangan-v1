<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\SaldoSantri;
use App\Models\Santri;
use App\Models\Transaksi;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WalletService
{
    public function __construct(private PushNotificationService $push) {}

    public function credit(Santri $santri, int $nominal, string $jenis, array $attrs = []): Transaksi
    {
        if ($nominal <= 0) {
            throw new InvalidArgumentException('Nominal kredit harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($santri, $nominal, $jenis, $attrs) {
            $saldo = $this->lockSaldo($santri);
            $sebelum = $saldo->saldo;
            $sesudah = $sebelum + $nominal;
            $saldo->update(['saldo' => $sesudah]);

            return Transaksi::create(array_merge([
                'santri_id' => $santri->id,
                'jenis' => $jenis,
                'arah' => Transaksi::ARAH_KREDIT,
                'nominal' => $nominal,
                'saldo_sebelum' => $sebelum,
                'saldo_sesudah' => $sesudah,
                'status' => Transaksi::STATUS_BERHASIL,
                'metode' => Transaksi::METODE_SISTEM,
            ], $attrs));
        });
    }

    public function debit(Santri $santri, int $nominal, string $jenis, array $attrs = []): Transaksi
    {
        if ($nominal <= 0) {
            throw new InvalidArgumentException('Nominal debit harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($santri, $nominal, $jenis, $attrs) {
            $saldo = $this->lockSaldo($santri);

            if ($nominal > $saldo->saldo) {
                throw new InsufficientBalanceException('Saldo santri tidak mencukupi untuk transaksi ini.');
            }

            $sebelum = $saldo->saldo;
            $sesudah = $sebelum - $nominal;
            $saldo->update(['saldo' => $sesudah]);

            $transaksi = Transaksi::create(array_merge([
                'santri_id' => $santri->id,
                'jenis' => $jenis,
                'arah' => Transaksi::ARAH_DEBIT,
                'nominal' => $nominal,
                'saldo_sebelum' => $sebelum,
                'saldo_sesudah' => $sesudah,
                'status' => Transaksi::STATUS_BERHASIL,
                'metode' => Transaksi::METODE_SISTEM,
            ], $attrs));

            DB::afterCommit(fn () => $this->notifyDebit($santri, $nominal, $jenis, $sesudah));

            return $transaksi;
        });
    }

    /**
     * Single choke point for every debit regardless of jenis (penarikan
     * tunai, pembayaran tagihan/kantin dari saldo, transfer antar santri) -
     * wording branches on $jenis so each still reads naturally. Never
     * throws - PushNotificationService::notify() already swallows its own
     * failures, and a wali not one of $santri->walis (none, in practice)
     * just means the loop below sends nothing.
     */
    private function notifyDebit(Santri $santri, int $nominal, string $jenis, int $saldoAkhir): void
    {
        [$title, $keterangan] = match ($jenis) {
            Transaksi::JENIS_PENARIKAN_TUNAI => ['Penarikan Tunai', 'penarikan tunai'],
            Transaksi::JENIS_PEMBAYARAN_TAGIHAN => ['Pembayaran Tagihan', 'pembayaran tagihan'],
            Transaksi::JENIS_PEMBAYARAN_KANTIN => ['Pembayaran Kantin', 'pembayaran kantin'],
            Transaksi::JENIS_TRANSFER_ANTAR_SANTRI => ['Transfer Saldo', 'transfer ke santri lain'],
            default => ['Saldo Berkurang', 'transaksi'],
        };

        $body = "Saldo {$santri->nama} berkurang Rp".number_format($nominal, 0, ',', '.')." untuk {$keterangan}. "
            .'Saldo akhir: Rp'.number_format($saldoAkhir, 0, ',', '.').'.';

        foreach ($santri->walis as $wali) {
            $this->push->notify($wali, $title, $body, [
                'type' => $jenis,
                'santri_id' => $santri->id,
                'saldo_akhir' => $saldoAkhir,
            ]);
        }
    }

    /**
     * Exposed (not private) so callers like TagihanService::bayarDariSaldo()
     * can read-then-decide against a locked balance before calling debit()
     * in the same outer transaction - re-acquiring lockForUpdate() on a row
     * already locked by the same DB transaction is safe in InnoDB.
     */
    public function lockSaldo(Santri $santri): SaldoSantri
    {
        return SaldoSantri::query()->lockForUpdate()->firstOrCreate(
            ['santri_id' => $santri->id],
            ['saldo' => 0]
        );
    }
}
