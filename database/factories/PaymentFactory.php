<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'type' => fake()->numberBetween(1,3),
            'status' => fake()->boolean(),
            'total_book_price' => fake()->numberBetween(50,1000) * 10,
            'discount_id' => fake()->numberBetween(1,10),
            'shipping_id' => fake()->unique()->numberBetween(1,10),
            'total' => fake()->numberBetween(50,1000) * 10,
            'paid_on' => fake()->dateTimeThisYear(),
            'description' => fake()->sentence(10, true)
        ];
    }
}
