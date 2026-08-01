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
        $faker = \Faker\Factory::create();

        return [
            'no_kk' => $faker->unique()->numerify('################'),
            'nama_kepala_keluarga' => $faker->name('male'),
            'alamat' => $faker->address(),
        ];
    }
}
