<?php

namespace Database\Factories;

use App\Models\JenisTagihan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JenisTagihan>
 */
class JenisTagihanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'kode' => fake()->unique()->bothify('JT-####'),
            'nama' => fake()->words(3, true),
            'nominal_default' => fake()->numberBetween(50000, 500000),
            'periode' => JenisTagihan::PERIODE_BULANAN,
            'is_active' => true,
        ];
    }
}
