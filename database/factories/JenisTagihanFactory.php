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
        $faker = \Faker\Factory::create();

        return [
            'kode' => $faker->unique()->bothify('JT-####'),
            'nama' => $faker->words(3, true),
            'nominal_default' => $faker->numberBetween(50000, 500000),
            'periode' => JenisTagihan::PERIODE_BULANAN,
            'is_active' => true,
        ];
    }
}
