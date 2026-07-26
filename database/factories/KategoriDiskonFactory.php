<?php

namespace Database\Factories;

use App\Models\KategoriDiskon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KategoriDiskon>
 */
class KategoriDiskonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nama' => fake()->unique()->words(2, true),
            'persentase' => fake()->numberBetween(5, 25),
            'is_active' => true,
        ];
    }
}
