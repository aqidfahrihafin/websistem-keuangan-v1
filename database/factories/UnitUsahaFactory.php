<?php

namespace Database\Factories;

use App\Models\UnitUsaha;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnitUsaha>
 */
class UnitUsahaFactory extends Factory
{
    public function definition(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'kode' => $faker->unique()->bothify('KANTIN-##??'),
            'nama' => 'Kantin '.$faker->unique()->firstName(),
            'tipe' => UnitUsaha::TIPE_KANTIN,
            'saldo_unit' => 0,
            'status' => UnitUsaha::STATUS_AKTIF,
        ];
    }
}
