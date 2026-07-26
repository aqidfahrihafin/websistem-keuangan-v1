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
        return [
            'kode_device' => fake()->unique()->bothify('KIOSK-##??'),
            'nama' => fake()->words(2, true),
            'lokasi' => fake()->streetName(),
            'tipe' => Device::TIPE_KIOSK_SALDO,
            'status' => 'aktif',
        ];
    }
}
