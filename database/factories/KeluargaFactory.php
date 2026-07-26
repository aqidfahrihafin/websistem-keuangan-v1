<?php

namespace Database\Factories;

use App\Models\Keluarga;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Keluarga>
 */
class KeluargaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'no_kk' => fake()->unique()->numerify('################'),
            'nama_kepala_keluarga' => fake()->name('male'),
            'alamat' => fake()->address(),
        ];
    }
}
