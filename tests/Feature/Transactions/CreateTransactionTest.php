<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $this->workspace->members()->attach($this->user->id, ['role' => 'owner']);
});

it('creates a projected transaction', function () {
    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/transactions",
        [
            'type' => 'expense',
            'concept' => 'Renta',
            'amount' => '5000.00',
            'currency' => 'MXN',
            'projected_date' => '2026-04-01',
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.concept', 'Renta')
        ->assertJsonPath('data.confirmed_at', null);

    expect(Transaction::where('concept', 'Renta')->exists())->toBeTrue();
});

it('creates a confirmed transaction when confirm is true', function () {
    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/transactions",
        [
            'type' => 'income',
            'concept' => 'Quincena',
            'amount' => '10000.00',
            'currency' => 'MXN',
            'projected_date' => now()->toDateString(),
            'confirm' => true,
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.concept', 'Quincena');

    expect(Transaction::where('concept', 'Quincena')->whereNotNull('confirmed_at')->exists())->toBeTrue();
});

it('fails with invalid transaction data', function () {
    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/transactions",
        [
            'type' => 'invalid-type',
            'concept' => '',
            'amount' => -100,
            'currency' => 'XX',
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'concept', 'amount', 'currency', 'projected_date']);
});

it('returns 401 when unauthenticated', function () {
    $response = $this->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/transactions",
        ['concept' => 'test']
    );

    $response->assertUnauthorized();
});

it('lists transactions filtered by month', function () {
    Transaction::factory()->create([
        'workspace_id' => $this->workspace->id,
        'projected_date' => '2026-04-15',
        'type' => 'expense',
    ]);
    Transaction::factory()->create([
        'workspace_id' => $this->workspace->id,
        'projected_date' => '2026-05-15',
        'type' => 'expense',
    ]);

    $response = $this->actingAs($this->user)->getJson(
        "/api/v1/workspaces/{$this->workspace->id}/transactions?month=2026-04"
    );

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(1);
});
