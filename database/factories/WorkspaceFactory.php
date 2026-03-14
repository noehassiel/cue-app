<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
class WorkspaceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->words(3, true),
            'currency' => fake()->randomElement(['MXN', 'USD', 'EUR']),
            'opening_balance' => fake()->randomFloat(4, 0, 50000),
            'projection_months' => fake()->randomElement([3, 6, 12]),
        ];
    }
}
