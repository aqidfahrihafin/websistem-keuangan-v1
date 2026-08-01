<?php

namespace Database\Factories;

use App\Models\KartuSantri;
use App\Models\Santri;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KartuSantri>
 */
class KartuSantriFactory extends Factory
{
    public function definition(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'santri_id' => Santri::factory(),
            'nomor_kartu' => $faker->unique()->numerify('KRT-######'),
            'uid_kartu' => $faker->unique()->bothify('UID-????????'),
            'fingerprint_template_ref' => $faker->unique()->bothify('FP-????????'),
            'status' => KartuSantri::STATUS_AKTIF,
            'diaktifkan_at' => now(),
        ];
    }
}
