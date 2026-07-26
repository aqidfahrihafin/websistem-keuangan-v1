<?php

namespace Database\Seeders;

use App\Models\KategoriDiskon;
use Illuminate\Database\Seeder;

class KategoriDiskonSeeder extends Seeder
{
    public function run(): void
    {
        KategoriDiskon::firstOrCreate(
            ['kode' => KategoriDiskon::KODE_BERSAUDARA],
            ['nama' => 'Santri Bersaudara', 'persentase' => 10, 'is_active' => true]
        );

        KategoriDiskon::firstOrCreate(
            ['kode' => KategoriDiskon::KODE_SANTRI_BARU],
            ['nama' => 'Santri Baru', 'persentase' => 5, 'is_active' => true]
        );

        // Jabatan santri di kepengurusan pondok - selalu ditandai manual oleh
        // admin, tidak ada sinyal otomatis untuk ini di data manapun.
        KategoriDiskon::firstOrCreate(
            ['nama' => 'Pengurus Kamar'],
            ['persentase' => 15, 'is_active' => true]
        );

        KategoriDiskon::firstOrCreate(
            ['nama' => 'Pengurus Pusat'],
            ['persentase' => 20, 'is_active' => true]
        );
    }
}
