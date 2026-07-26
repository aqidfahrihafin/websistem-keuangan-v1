<?php

namespace Database\Seeders;

use App\Models\Lembaga;
use Illuminate\Database\Seeder;

class LembagaSeeder extends Seeder
{
    public function run(): void
    {
        Lembaga::firstOrCreate(
            ['kode' => 'PONDOK-PUSAT'],
            ['nama' => 'Pondok Pesantren Latee', 'tipe' => Lembaga::TIPE_PONDOK_PUSAT, 'is_active' => true]
        );

        Lembaga::firstOrCreate(
            ['kode' => 'MTS-LATEE'],
            ['nama' => 'MTs Latee', 'tipe' => Lembaga::TIPE_SEKOLAH_FORMAL, 'is_active' => true]
        );

        Lembaga::firstOrCreate(
            ['kode' => 'MA-LATEE'],
            ['nama' => 'MA Latee', 'tipe' => Lembaga::TIPE_SEKOLAH_FORMAL, 'is_active' => true]
        );
    }
}
