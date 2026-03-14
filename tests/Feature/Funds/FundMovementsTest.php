<?php

use App\Models\Fund;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $this->workspace->members()->attach($this->user->id, ['role' => 'owner']);
    $this->fund = Fund::factory()->create([
        'workspace_id' => $this->workspace->id,
        'current_balance' => '0.0000',
        'currency' => 'MXN',
    ]);
});

it('deposits into a fund and creates an expense transaction', function () {
    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/funds/{$this->fund->id}/deposit",
        [
            'amount' => '5000.00',
            'note' => 'Monthly savings',
        ]
    );

    $response->assertSuccessful()
        ->assertJsonPath('data.movement.type', 'deposit');

    $this->fund->refresh();
    expect((float) $this->fund->current_balance)->toBe(5000.0);

    $transaction = Transaction::where('category', 'fund_allocation')
        ->where('type', 'expense')
        ->first();
    expect($transaction)->not->toBeNull();
    expect($transaction->confirmed_at)->not->toBeNull();
});

it('withdraws from a fund and creates an income transaction', function () {
    $this->fund->update(['current_balance' => '10000.0000']);

    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/funds/{$this->fund->id}/withdraw",
        [
            'amount' => '3000.00',
            'note' => 'Need cash for rent',
        ]
    );

    $response->assertSuccessful()
        ->assertJsonPath('data.movement.type', 'withdrawal');

    $this->fund->refresh();
    expect((float) $this->fund->current_balance)->toBe(7000.0);

    $transaction = Transaction::where('category', 'fund_allocation')
        ->where('type', 'income')
        ->first();
    expect($transaction)->not->toBeNull();
});

it('prevents withdrawal exceeding fund balance', function () {
    $this->fund->update(['current_balance' => '1000.0000']);

    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/funds/{$this->fund->id}/withdraw",
        ['amount' => '5000.00']
    );

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Insufficient fund balance.');

    $this->fund->refresh();
    expect((float) $this->fund->current_balance)->toBe(1000.0);
});

it('lists fund movements', function () {
    $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/funds/{$this->fund->id}/deposit",
        ['amount' => '2000.00']
    );

    $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/funds/{$this->fund->id}/deposit",
        ['amount' => '3000.00']
    );

    $response = $this->actingAs($this->user)->getJson(
        "/api/v1/workspaces/{$this->workspace->id}/funds/{$this->fund->id}/movements"
    );

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(2);
});

it('uses custom movement date when provided', function () {
    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/funds/{$this->fund->id}/deposit",
        [
            'amount' => '1000.00',
            'movement_date' => '2026-03-01',
        ]
    );

    $response->assertSuccessful()
        ->assertJsonPath('data.movement.movement_date', '2026-03-01');
});

it('fails deposit with invalid data', function () {
    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/funds/{$this->fund->id}/deposit",
        ['amount' => -100]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});
