<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Models\UnitUsaha;
use App\Models\UnitUsahaTransaksi;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * WalletService's counterpart for a UnitUsaha's saldo_unit - kept as a
 * separate service (not a generalization of WalletService) because
 * WalletService is hard-coded to Santri (locks SaldoSantri, writes
 * Transaksi.santri_id which is a required column) and a unit usaha isn't a
 * santri. saldo_unit lives directly on the unit_usahas row, so locking
 * that row itself (instead of a separate balance table) is enough.
 */
class UnitUsahaWalletService
{
    public function credit(UnitUsaha $unitUsaha, int $nominal, string $jenis, array $attrs = []): UnitUsahaTransaksi
    {
        if ($nominal <= 0) {
            throw new InvalidArgumentException('Nominal kredit harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($unitUsaha, $nominal, $jenis, $attrs) {
            $locked = $this->lockSaldo($unitUsaha);
            $sebelum = $locked->saldo_unit;
            $sesudah = $sebelum + $nominal;
            $locked->update(['saldo_unit' => $sesudah]);

            return UnitUsahaTransaksi::create(array_merge([
                'unit_usaha_id' => $unitUsaha->id,
                'jenis' => $jenis,
                'arah' => UnitUsahaTransaksi::ARAH_KREDIT,
                'nominal' => $nominal,
                'saldo_sebelum' => $sebelum,
                'saldo_sesudah' => $sesudah,
            ], $attrs));
        });
    }

    public function debit(UnitUsaha $unitUsaha, int $nominal, string $jenis, array $attrs = []): UnitUsahaTransaksi
    {
        if ($nominal <= 0) {
            throw new InvalidArgumentException('Nominal debit harus lebih besar dari 0.');
        }

        return DB::transaction(function () use ($unitUsaha, $nominal, $jenis, $attrs) {
            $locked = $this->lockSaldo($unitUsaha);

            if ($nominal > $locked->saldo_unit) {
                throw new InsufficientBalanceException('Saldo unit usaha tidak mencukupi untuk transaksi ini.');
            }

            $sebelum = $locked->saldo_unit;
            $sesudah = $sebelum - $nominal;
            $locked->update(['saldo_unit' => $sesudah]);

            return UnitUsahaTransaksi::create(array_merge([
                'unit_usaha_id' => $unitUsaha->id,
                'jenis' => $jenis,
                'arah' => UnitUsahaTransaksi::ARAH_DEBIT,
                'nominal' => $nominal,
                'saldo_sebelum' => $sebelum,
                'saldo_sesudah' => $sesudah,
            ], $attrs));
        });
    }

    /**
     * Exposed (not private) so callers like UnitUsahaPenarikanService::fulfill()
     * can act against a locked balance in their own outer transaction - same
     * reasoning as WalletService::lockSaldo().
     */
    public function lockSaldo(UnitUsaha $unitUsaha): UnitUsaha
    {
        return UnitUsaha::query()->lockForUpdate()->findOrFail($unitUsaha->id);
    }
}
