<?php

use App\Models\Fund;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;
use App\Services\CashFlowService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create([
        'owner_id' => $this->user->id,
        'opening_balance' => '10000.0000',
        'currency' => 'MXN',
    ]);
    $this->service = app(CashFlowService::class);
});

it('calculates operational balance correctly', function () {
    Transaction::factory()->confirmed()->income()->create([
        'workspace_id' => $this->workspace->id,
        'amount' => '5000.0000',
        'currency' => 'MXN',
    ]);
    Transaction::factory()->confirmed()->expense()->create([
        'workspace_id' => $this->workspace->id,
        'amount' => '2000.0000',
        'currency' => 'MXN',
    ]);

    $balance = $this->service->getOperationalBalance($this->workspace);

    // 10000 + 5000 - 2000 = 13000
    expect($balance)->toBe('13000.0000');
});

it('subtracts fund balances from operational balance', function () {
    Fund::factory()->create([
        'workspace_id' => $this->workspace->id,
        'current_balance' => '3000.0000',
        'currency' => 'MXN',
    ]);

    $balance = $this->service->getOperationalBalance($this->workspace);

    // 10000 - 3000 = 7000
    expect($balance)->toBe('7000.0000');
});

it('calculates projected balance including unconfirmed transactions up to target date', function () {
    Transaction::factory()->income()->create([
        'workspace_id' => $this->workspace->id,
        'amount' => '5000.0000',
        'currency' => 'MXN',
        'projected_date' => now()->addDays(5)->toDateString(),
        'confirmed_at' => null,
    ]);

    $balance = $this->service->getProjectedBalance($this->workspace, now()->addMonth());

    // 10000 (operational) + 5000 (projected income) = 15000
    expect($balance)->toBe('15000.0000');
});

it('returns monthly periods array with correct structure', function () {
    $periods = $this->service->getMonthlyPeriods($this->workspace, 3);

    expect($periods)->toHaveCount(3);
    expect($periods[0])->toHaveKeys([
        'month',
        'projected_income',
        'projected_expenses',
        'projected_net',
        'confirmed_income',
        'confirmed_expenses',
        'balance_start',
        'balance_end',
        'deficit',
    ]);
});

it('marks a period as deficit when balance_end is negative', function () {
    Transaction::factory()->expense()->create([
        'workspace_id' => $this->workspace->id,
        'amount' => '50000.0000',
        'currency' => 'MXN',
        'projected_date' => now()->startOfMonth()->toDateString(),
        'confirmed_at' => null,
    ]);

    $periods = $this->service->getMonthlyPeriods($this->workspace, 1);

    expect($periods[0]['deficit'])->toBeTrue();
});
