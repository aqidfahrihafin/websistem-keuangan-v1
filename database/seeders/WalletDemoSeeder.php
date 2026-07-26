<?php

namespace Database\Seeders;

use App\Models\Santri;
use App\Models\Transaksi;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Database\Seeder;

class WalletDemoSeeder extends Seeder
{
    public function run(): void
    {
        $wallet = app(WalletService::class);
        $admin = User::where('email', 'admin@pesantren.test')->first();

        $ahmad = Santri::where('nis', '1001000001')->first();
        $rizki = Santri::where('nis', '1001000002')->first();

        if ($ahmad) {
            $wallet->credit($ahmad, 200000, Transaksi::JENIS_TOPUP_TUNAI, [
                'metode' => Transaksi::METODE_TUNAI,
                'diproses_oleh' => $admin?->id,
            ]);
        }

        if ($rizki) {
            $wallet->credit($rizki, 100000, Transaksi::JENIS_TOPUP_TUNAI, [
                'metode' => Transaksi::METODE_TUNAI,
                'diproses_oleh' => $admin?->id,
            ]);
        }
    }
}
