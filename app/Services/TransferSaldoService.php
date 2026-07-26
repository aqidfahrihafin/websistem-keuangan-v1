<?php

namespace App\Services;

use App\Exceptions\SaldoDiBawahMinimumException;
use App\Models\Santri;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Moves saldo directly between two santri who share the same Kartu
 * Keluarga - a debit on one WalletService ledger and a credit on another,
 * atomically, same shape as KantinPembayaranService::bayar() (which does
 * santri -> unit usaha instead of santri -> santri). Each side's Transaksi
 * references the *other santri* (not the sibling transaction row), since
 * Transaksi is an immutable ledger and both rows can't know each other's id
 * until after both already exist.
 */
class TransferSaldoService
{
    public function __construct(
        private WalletService $wallet,
        private SaldoFloorService $saldoFloor,
    ) {}

    /**
     * @throws SaldoDiBawahMinimumException
     * @throws \App\Exceptions\InsufficientBalanceException
     * @throws InvalidArgumentException
     * @return array{debit: Transaksi, credit: Transaksi}
     */
    public function transfer(Santri $dari, Santri $ke, int $nominal, User $diprosesOleh): array
    {
        if ($dari->id === $ke->id) {
            throw new InvalidArgumentException('Tidak bisa transfer ke santri yang sama.');
        }

        if ($dari->keluarga_id === null || $dari->keluarga_id !== $ke->keluarga_id) {
            throw new InvalidArgumentException('Santri tujuan harus satu Kartu Keluarga.');
        }

        if ($ke->status !== Santri::STATUS_AKTIF) {
            throw new InvalidArgumentException('Santri tujuan sedang tidak aktif.');
        }

        return DB::transaction(function () use ($dari, $ke, $nominal, $diprosesOleh) {
            // Locked here (same outer transaction) so the floor check reads
            // an up-to-date balance rather than a stale one two concurrent
            // transfers could each individually pass against - same
            // guard/ordering as TagihanService::bayarDariSaldo(). Only
            // checked when saldo can cover the transfer at all - otherwise
            // debit() below throws its own InsufficientBalanceException,
            // which must win (truly not enough money, vs enough but
            // policy-blocked, are different facts the wali needs to see).
            $saldoDari = $this->wallet->lockSaldo($dari);
            $minimal = $this->saldoFloor->minimal();

            if ($saldoDari->saldo >= $nominal && $saldoDari->saldo - $nominal < $minimal) {
                throw new SaldoDiBawahMinimumException(
                    'Transfer tidak bisa dilakukan karena akan membuat saldo '.$dari->nama
                    .' di bawah batas minimum Rp '.number_format($minimal, 0, ',', '.').'.'
                );
            }

            $debit = $this->wallet->debit(
                $dari,
                $nominal,
                Transaksi::JENIS_TRANSFER_ANTAR_SANTRI,
                [
                    'diproses_oleh' => $diprosesOleh->id,
                    'referensi_type' => Santri::class,
                    'referensi_id' => $ke->id,
                ]
            );

            $credit = $this->wallet->credit(
                $ke,
                $nominal,
                Transaksi::JENIS_TRANSFER_ANTAR_SANTRI,
                [
                    'diproses_oleh' => $diprosesOleh->id,
                    'referensi_type' => Santri::class,
                    'referensi_id' => $dari->id,
                ]
            );

            return ['debit' => $debit, 'credit' => $credit];
        });
    }
}
