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
        return [
            'user_id' => User::factory(),
            'santri_id' => Santri::factory(),
            'nominal_diminta' => fake()->numberBetween(50000, 300000),
            'midtrans_order_id' => fake()->unique()->bothify('TOPUP-########'),
            'status' => TopupWali::STATUS_PENDING,
        ];
    }
}
