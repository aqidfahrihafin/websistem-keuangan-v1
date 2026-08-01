<?php

namespace Database\Factories;

use App\Models\KebijakanKantin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KebijakanKantin>
 */
class KebijakanKantinFactory extends Factory
{
    public function definition(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'nama' => 'Kebijakan '.$faker->word(),
            'limit_harian' => 20000,
            'is_active' => true,
            'effective_from' => now()->subDay()->toDateString(),
        ];
    }
}
