<?php

namespace Database\Factories;

use App\Models\Lembaga;
use Illuminate\Database\Eloquent\Factories\Factory;

class LembagaFactory extends Factory
{
    protected $model = Lembaga::class;

    public function definition(): array
    {
        return [
            'kode' => strtoupper($this->faker->unique()->lexify('LBG-????')),
            'nama' => $this->faker->company(),
            'tipe' => Lembaga::TIPE_SEKOLAH_FORMAL,
            'alamat' => $this->faker->address(),
            'is_active' => true,
        ];
    }
}
