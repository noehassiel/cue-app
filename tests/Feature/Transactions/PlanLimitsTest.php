<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $this->workspace->members()->attach($this->user->id, ['role' => 'owner']);
});

it('blocks transaction creation when free plan limit of 50 per month is reached', function () {
    Transaction::factory()->count(50)->create([
        'workspace_id' => $this->workspace->id,
        'projected_date' => now()->format('Y-m') . '-01',
    ]);

    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/transactions",
        [
            'type' => 'expense',
            'concept' => 'Over limit',
            'amount' => '100.00',
            'currency' => 'MXN',
            'projected_date' => now()->toDateString(),
        ]
    );

    $response->assertStatus(402)
        ->assertJsonPath('limit', 50);
});

it('allows transaction creation when under the free plan limit', function () {
    Transaction::factory()->count(49)->create([
        'workspace_id' => $this->workspace->id,
        'projected_date' => now()->format('Y-m') . '-01',
    ]);

    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/transactions",
        [
            'type' => 'expense',
            'concept' => 'Within limit',
            'amount' => '100.00',
            'currency' => 'MXN',
            'projected_date' => now()->toDateString(),
        ]
    );

    $response->assertCreated();
});

it('blocks second workspace creation on free plan', function () {
    $response = $this->actingAs($this->user)->postJson('/api/v1/workspaces', [
        'name' => 'Second Workspace',
        'currency' => 'USD',
    ]);

    $response->assertStatus(402)
        ->assertJsonPath('limit', 1);
});
