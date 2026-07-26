<?php

namespace Database\Seeders;

use App\Models\KebijakanPenarikan;
use Illuminate\Database\Seeder;

class KebijakanPenarikanSeeder extends Seeder
{
    public function run(): void
    {
        KebijakanPenarikan::firstOrCreate(
            ['nama' => 'Kebijakan Penarikan Standar'],
            [
                'jam_mulai' => '08:00',
                'jam_selesai' => '15:00',
                'limit_harian' => 50000,
                'is_active' => true,
                'effective_from' => now()->subMonth()->toDateString(),
            ]
        );
    }
}
