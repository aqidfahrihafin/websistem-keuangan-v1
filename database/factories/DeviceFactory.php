<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Device>
 */
class DeviceFactory extends Factory
{
    public function definition(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'kode_device' => $faker->unique()->bothify('KIOSK-##??'),
            'nama' => $faker->words(2, true),
            'lokasi' => $faker->streetName(),
            'tipe' => Device::TIPE_KIOSK_SALDO,
            'status' => 'aktif',
        ];
    }
}
