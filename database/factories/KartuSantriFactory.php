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
        return [
            'santri_id' => Santri::factory(),
            'nomor_kartu' => fake()->unique()->numerify('KRT-######'),
            'uid_kartu' => fake()->unique()->bothify('UID-????????'),
            'fingerprint_template_ref' => fake()->unique()->bothify('FP-????????'),
            'status' => KartuSantri::STATUS_AKTIF,
            'diaktifkan_at' => now(),
        ];
    }
}
