<?php

namespace Database\Factories;

use App\Models\Fund;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Fund>
 */
class FundFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'target_amount' => fake()->optional()->randomFloat(4, 1000, 50000),
            'current_balance' => '0.0000',
            'currency' => 'MXN',
            'target_date' => fake()->optional()->dateTimeBetween('now', '+2 years')?->format('Y-m-d'),
            'color' => fake()->optional()->hexColor(),
        ];
    }
}
