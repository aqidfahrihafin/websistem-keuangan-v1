<?php

namespace Database\Seeders;

use App\Models\JenisTagihan;
use Illuminate\Database\Seeder;

class JenisTagihanSeeder extends Seeder
{
    public function run(): void
    {
        JenisTagihan::firstOrCreate(
            ['kode' => 'SPP-BULANAN'],
            ['nama' => 'SPP Bulanan', 'nominal_default' => 150000, 'periode' => JenisTagihan::PERIODE_BULANAN, 'is_active' => true, 'berlaku_diskon' => true]
        );

        JenisTagihan::firstOrCreate(
            ['kode' => 'MAKAN-BULANAN'],
            ['nama' => 'Uang Makan Bulanan', 'nominal_default' => 300000, 'periode' => JenisTagihan::PERIODE_BULANAN, 'is_active' => true]
        );

        JenisTagihan::firstOrCreate(
            ['kode' => 'DAFTAR-ULANG'],
            ['nama' => 'Daftar Ulang Tahunan', 'nominal_default' => 1000000, 'periode' => JenisTagihan::PERIODE_TAHUNAN, 'is_active' => true]
        );
    }
}
