<?php

use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $this->workspace->members()->attach($this->user->id, ['role' => 'owner']);
});

it('confirms a projected transaction', function () {
    $transaction = Transaction::factory()->create([
        'workspace_id' => $this->workspace->id,
        'confirmed_at' => null,
    ]);

    $response = $this->actingAs($this->user)->patchJson(
        "/api/v1/workspaces/{$this->workspace->id}/transactions/{$transaction->id}/confirm"
    );

    $response->assertSuccessful();

    expect($transaction->fresh()->confirmed_at)->not->toBeNull();
});

it('confirms a transaction with a specific confirmed_at date', function () {
    $transaction = Transaction::factory()->create([
        'workspace_id' => $this->workspace->id,
        'confirmed_at' => null,
    ]);

    $confirmedAt = '2026-03-10T14:00:00Z';

    $response = $this->actingAs($this->user)->patchJson(
        "/api/v1/workspaces/{$this->workspace->id}/transactions/{$transaction->id}/confirm",
        ['confirmed_at' => $confirmedAt]
    );

    $response->assertSuccessful();

    $refreshed = $transaction->fresh();
    expect($refreshed->confirmed_at)->not->toBeNull();
});

it('returns 404 when transaction belongs to another workspace', function () {
    $otherWorkspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $transaction = Transaction::factory()->create(['workspace_id' => $otherWorkspace->id]);

    $response = $this->actingAs($this->user)->patchJson(
        "/api/v1/workspaces/{$this->workspace->id}/transactions/{$transaction->id}/confirm"
    );

    $response->assertNotFound();
});
