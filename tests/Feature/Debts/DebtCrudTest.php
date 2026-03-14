<?php

use App\Models\Debt;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $this->workspace->members()->attach($this->user->id, ['role' => 'owner']);
});

it('creates a debt and auto-generates installments', function () {
    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts",
        [
            'name' => 'Boleto de avion MSI',
            'total_amount' => '12000.0000',
            'installment_amount' => '2000.0000',
            'total_installments' => 6,
            'currency' => 'MXN',
            'start_date' => '2026-04-01',
            'payment_day' => 15,
        ]
    );

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Boleto de avion MSI')
        ->assertJsonPath('data.total_installments', 6)
        ->assertJsonPath('data.remaining_installments', 6);

    $debt = Debt::where('name', 'Boleto de avion MSI')->first();
    expect($debt)->not->toBeNull();
    expect($debt->installments)->toHaveCount(6);

    $projectedTransactions = Transaction::where('category', 'debt_payment')
        ->where('workspace_id', $this->workspace->id)
        ->count();
    expect($projectedTransactions)->toBe(6);
});

it('lists debts with installment data', function () {
    $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts",
        [
            'name' => 'Test Debt',
            'total_amount' => '6000.0000',
            'installment_amount' => '1000.0000',
            'total_installments' => 6,
            'currency' => 'MXN',
            'start_date' => '2026-04-01',
        ]
    );

    $response = $this->actingAs($this->user)->getJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts"
    );

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.remaining_installments'))->toBe(6);
});

it('shows a debt', function () {
    $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts",
        [
            'name' => 'Show Debt',
            'total_amount' => '3000.0000',
            'installment_amount' => '1000.0000',
            'total_installments' => 3,
            'currency' => 'MXN',
            'start_date' => '2026-04-01',
        ]
    );

    $debt = Debt::where('name', 'Show Debt')->first();

    $response = $this->actingAs($this->user)->getJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts/{$debt->id}"
    );

    $response->assertSuccessful()
        ->assertJsonPath('data.name', 'Show Debt');
});

it('updates a debt', function () {
    $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts",
        [
            'name' => 'Original Name',
            'total_amount' => '3000.0000',
            'installment_amount' => '1000.0000',
            'total_installments' => 3,
            'currency' => 'MXN',
            'start_date' => '2026-04-01',
        ]
    );

    $debt = Debt::where('name', 'Original Name')->first();

    $response = $this->actingAs($this->user)->patchJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts/{$debt->id}",
        ['name' => 'Updated Name', 'notes' => 'Added notes']
    );

    $response->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.notes', 'Added notes');
});

it('deletes a debt and its unpaid installment transactions', function () {
    $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts",
        [
            'name' => 'Delete Me',
            'total_amount' => '4000.0000',
            'installment_amount' => '1000.0000',
            'total_installments' => 4,
            'currency' => 'MXN',
            'start_date' => '2026-04-01',
        ]
    );

    $debt = Debt::where('name', 'Delete Me')->first();
    $transactionCount = Transaction::where('workspace_id', $this->workspace->id)->count();
    expect($transactionCount)->toBe(4);

    $response = $this->actingAs($this->user)->deleteJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts/{$debt->id}"
    );

    $response->assertSuccessful()
        ->assertJsonPath('message', 'Debt deleted');

    expect(Debt::withTrashed()->find($debt->id)->deleted_at)->not->toBeNull();

    $remainingTransactions = Transaction::where('workspace_id', $this->workspace->id)
        ->whereNull('deleted_at')
        ->count();
    expect($remainingTransactions)->toBe(0);
});

it('fails with invalid debt data', function () {
    $response = $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts",
        [
            'name' => '',
            'total_amount' => -1000,
            'installment_amount' => 0,
            'total_installments' => 0,
            'currency' => 'X',
        ]
    );

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'total_amount', 'installment_amount', 'total_installments', 'currency', 'start_date']);
});

it('returns 401 when unauthenticated', function () {
    $response = $this->getJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts"
    );

    $response->assertUnauthorized();
});
