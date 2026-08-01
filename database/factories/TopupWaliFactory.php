<?php

namespace Database\Factories;

use App\Models\Santri;
use App\Models\TopupWali;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TopupWali>
 */
class TopupWaliFactory extends Factory
{
    public function definition(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'user_id' => User::factory(),
            'santri_id' => Santri::factory(),
            'nominal_diminta' => $faker->numberBetween(50000, 300000),
            'midtrans_order_id' => $faker->unique()->bothify('TOPUP-########'),
            'status' => TopupWali::STATUS_PENDING,
        ];
    }
}
