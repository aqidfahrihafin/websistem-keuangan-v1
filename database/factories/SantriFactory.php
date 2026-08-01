<?php

namespace Database\Factories;

use App\Models\Santri;
use App\Models\Keluarga;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Santri>
 */
class SantriFactory extends Factory
{
    public function definition(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'nis' => $faker->unique()->numerify('##########'),
            'nama' => $faker->name(),
            'tempat_lahir' => $faker->city(),
            'tanggal_lahir' => $faker->dateTimeBetween('-18 years', '-12 years'),
            'jenis_kelamin' => $faker->randomElement(['L', 'P']),
            'alamat' => $faker->address(),
            'status' => Santri::STATUS_AKTIF,
            'tanggal_masuk' => $faker->dateTimeBetween('-3 years', 'now'),
            'keluarga_id' => Keluarga::factory(),
        ];
    }
}
