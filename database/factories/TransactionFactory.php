<?php

namespace Database\Factories;

use App\Models\Transaction;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'type' => fake()->randomElement(['income', 'expense']),
            'concept' => fake()->words(3, true),
            'amount' => fake()->randomFloat(4, 100, 10000),
            'currency' => 'MXN',
            'projected_date' => fake()->dateTimeBetween('-1 month', '+3 months')->format('Y-m-d'),
            'confirmed_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'confirmed_at' => now(),
        ]);
    }

    public function income(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'income',
        ]);
    }

    public function expense(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'expense',
        ]);
    }
}
