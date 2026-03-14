<?php

namespace Database\Factories;

use App\Models\Debt;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Debt>
 */
class DebtFactory extends Factory
{
    public function definition(): array
    {
        $totalInstallments = fake()->numberBetween(3, 24);
        $installmentAmount = fake()->randomFloat(4, 500, 5000);

        return [
            'workspace_id' => Workspace::factory(),
            'name' => fake()->words(3, true),
            'total_amount' => bcmul((string) $installmentAmount, (string) $totalInstallments, 4),
            'installment_amount' => number_format($installmentAmount, 4, '.', ''),
            'total_installments' => $totalInstallments,
            'paid_installments' => 0,
            'currency' => 'MXN',
            'start_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'payment_day' => fake()->optional()->numberBetween(1, 28),
        ];
    }
}
