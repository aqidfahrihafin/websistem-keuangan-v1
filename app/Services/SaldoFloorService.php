<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Admin-configurable transaction limits, edited together on the Pengaturan
 * Midtrans page: the minimum saldo santri a wali is required to leave
 * untouched when paying a tagihan from saldo (also enforced for transfer
 * antar santri and bayar kantin - see TransferSaldoService/
 * KantinPembayaranService), and the maximum nominal allowed on a single
 * money-moving request (top up, transfer, bayar kantin, bayar tagihan dari
 * saldo) - a fat-finger/abuse safety net, not a real business limit. Kept
 * as its own service (not folded into TagihanService or TopupWaliService)
 * so TagihanService can read it without a circular dependency, since
 * TopupWaliService already depends on TagihanService.
 */
class SaldoFloorService
{
    private const SETTING_KEY = 'tagihan_minimal_saldo_setelah_bayar';

    private const DEFAULT_MINIMAL_SALDO = 100000;

    private const MAKSIMAL_SETTING_KEY = 'transaksi_maksimal_nominal';

    private const DEFAULT_MAKSIMAL_NOMINAL = 50000000;

    public function minimal(): int
    {
        $stored = Setting::get(self::SETTING_KEY);

        return $stored !== null ? (int) $stored : self::DEFAULT_MINIMAL_SALDO;
    }

    public function simpan(int $nominal): void
    {
        Setting::put(self::SETTING_KEY, (string) $nominal);
    }

    public function maksimalNominal(): int
    {
        $stored = Setting::get(self::MAKSIMAL_SETTING_KEY);

        return $stored !== null ? (int) $stored : self::DEFAULT_MAKSIMAL_NOMINAL;
    }

    public function simpanMaksimalNominal(int $nominal): void
    {
        Setting::put(self::MAKSIMAL_SETTING_KEY, (string) $nominal);
    }
}
