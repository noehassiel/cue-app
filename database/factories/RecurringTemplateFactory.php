<?php

namespace Database\Factories;

use App\Models\RecurringTemplate;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurringTemplate>
 */
class RecurringTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'concept' => fake()->words(3, true),
            'type' => fake()->randomElement(['income', 'expense']),
            'amount' => fake()->randomFloat(4, 100, 10000),
            'currency' => 'MXN',
            'frequency' => fake()->randomElement(['weekly', 'biweekly', 'monthly']),
            'frequency_day' => fake()->numberBetween(1, 28),
            'next_date' => now()->addDays(5)->format('Y-m-d'),
            'generate_ahead_days' => 90,
            'is_active' => true,
        ];
    }
}
