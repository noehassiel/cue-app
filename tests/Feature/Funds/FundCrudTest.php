<?php

use App\Models\Fund;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $this->workspace->members()->attach($this->user->id, ['role' => 'owner']);
});

it('lists funds for a workspace', function () {
    Fund::factory()->count(3)->create(['workspace_id' => $this->workspace->id]);

    $response = $this->actingAs($this->user)->getJson(
        "/api/v1/workspaces/{$this->workspace->id}/funds"
    );

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(3);
});

it('creates a fund', function () {
    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/funds",
        [
            'name' => 'Viaje Illi',
            'description' => 'Savings for Illi trip',
            'target_amount' => '25000.0000',
            'currency' => 'MXN',
            'target_date' => '2026-12-15',
            'color' => '#FF5733',
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Viaje Illi')
        ->assertJsonPath('data.current_balance', '0.0000')
        ->assertJsonPath('data.target_amount', '25000.0000');

    expect(Fund::where('name', 'Viaje Illi')->exists())->toBeTrue();
});

it('shows a fund', function () {
    $fund = Fund::factory()->create(['workspace_id' => $this->workspace->id]);

    $response = $this->actingAs($this->user)->getJson(
        "/api/v1/workspaces/{$this->workspace->id}/funds/{$fund->id}"
    );

    $response->assertSuccessful()
        ->assertJsonPath('data.id', $fund->id);
});

it('updates a fund', function () {
    $fund = Fund::factory()->create(['workspace_id' => $this->workspace->id]);

    $response = $this->actingAs($this->user)->patchJson(
        "/api/v1/workspaces/{$this->workspace->id}/funds/{$fund->id}",
        ['name' => 'Updated Fund Name']
    );

    $response->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Fund Name');
});

it('deletes a fund with zero balance', function () {
    $fund = Fund::factory()->create([
        'workspace_id' => $this->workspace->id,
        'current_balance' => '0.0000',
    ]);

    $response = $this->actingAs($this->user)->deleteJson(
        "/api/v1/workspaces/{$this->workspace->id}/funds/{$fund->id}"
    );

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Fund deleted');

    expect(Fund::withTrashed()->find($fund->id)->deleted_at)->not->toBeNull();
});

it('prevents deleting a fund with non-zero balance', function () {
    $fund = Fund::factory()->create([
        'workspace_id' => $this->workspace->id,
        'current_balance' => '5000.0000',
    ]);

    $response = $this->actingAs($this->user)->deleteJson(
        "/api/v1/workspaces/{$this->workspace->id}/funds/{$fund->id}"
    );

    $response->assertStatus(422);
    expect(Fund::find($fund->id))->not->toBeNull();
});

it('fails with invalid fund data', function () {
    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/funds",
        [
            'name' => '',
            'currency' => 'XX',
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'currency']);
});

it('returns 401 when unauthenticated', function () {
    $response = $this->getJson(
        "/api/v1/workspaces/{$this->workspace->id}/funds"
    );

    $response->assertUnauthorized();
});
