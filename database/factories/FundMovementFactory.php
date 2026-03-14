<?php

namespace Database\Factories;

use App\Models\Fund;
use App\Models\FundMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FundMovement>
 */
class FundMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'fund_id' => Fund::factory(),
            'type' => fake()->randomElement(['deposit', 'withdrawal']),
            'amount' => fake()->randomFloat(4, 100, 5000),
            'note' => fake()->optional()->sentence(),
            'movement_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
        ];
    }

    public function deposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deposit',
        ]);
    }

    public function withdrawal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'withdrawal',
        ]);
    }
}
