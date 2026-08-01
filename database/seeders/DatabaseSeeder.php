<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Model events are intentionally left enabled (no WithoutModelEvents) -
     * SantriObserver/UserObserver auto-linking and the real WalletService/
     * TagihanService calls below rely on them to produce a realistic demo
     * dataset, and doing so doubles as a smoke test of that wiring.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            LembagaSeeder::class,
            UserSeeder::class,
            KategoriDiskonSeeder::class,
            KeluargaSantriSeeder::class,
            RayonKamarSeeder::class,
            KartuSantriSeeder::class,
            JenisTagihanSeeder::class,
            PeriodeSeeder::class,
            KebijakanPenarikanSeeder::class,
            DeviceSeeder::class,
        ]);

        // TagihanSeeder/WalletDemoSeeder deliberately left out of the
        // default run - they generate demo tagihan and credit demo saldo/
        // transaksi history, which gets in the way when the goal is testing
        // the tagihan/saldo flow from a genuinely clean slate (every santri
        // starts at saldo 0 with zero tagihan). JenisTagihan/Periode master
        // data is still seeded above, so tagihan can be generated for real
        // through the admin panel same as production use.
    }
}
