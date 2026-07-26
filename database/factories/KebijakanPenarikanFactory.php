<?php

namespace Database\Factories;

use App\Models\KebijakanPenarikan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KebijakanPenarikan>
 */
class KebijakanPenarikanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => 'Kebijakan '.fake()->word(),
            'jam_mulai' => '08:00:00',
            'jam_selesai' => '15:00:00',
            'limit_harian' => 50000,
            'is_active' => true,
            'effective_from' => now()->subDay()->toDateString(),
        ];
    }
}
