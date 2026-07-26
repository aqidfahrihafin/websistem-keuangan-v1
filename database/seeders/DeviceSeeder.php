<?php

namespace Database\Seeders;

use App\Models\Device;
use Illuminate\Database\Seeder;

class DeviceSeeder extends Seeder
{
    public function run(): void
    {
        $devices = [
            ['kode_device' => 'KIOSK-01', 'nama' => 'Kiosk Saldo Aula Utama', 'lokasi' => 'Aula Utama', 'tipe' => Device::TIPE_KIOSK_SALDO, 'status' => 'aktif'],
            // The self-service withdrawal flow (Kios\CekSaldo) only offers
            // cash-out on a kiosk_penarikan device that's aktif - seed one so
            // /kios/KIOSK-PENARIKAN-01 demos that flow out of the box.
            ['kode_device' => 'KIOSK-PENARIKAN-01', 'nama' => 'Kiosk Penarikan Aula Utama', 'lokasi' => 'Aula Utama', 'tipe' => Device::TIPE_KIOSK_PENARIKAN, 'status' => 'aktif'],
        ];

        foreach ($devices as $attributes) {
            $device = Device::firstOrCreate(['kode_device' => $attributes['kode_device']], $attributes);

            if ($device->tokens()->count() === 0) {
                $token = $device->createToken('kiosk-dev-token', ['kiosk'])->plainTextToken;
                $this->command?->info("Device token untuk {$device->kode_device}: {$token}");
            }
        }
    }
}
