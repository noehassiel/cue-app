<?php

use App\Models\Debt;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Workspace;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
    $this->workspace->members()->attach($this->user->id, ['role' => 'owner']);

    $this->actingAs($this->user)->postJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts",
        [
            'name' => 'Test Debt',
            'total_amount' => '6000.0000',
            'installment_amount' => '2000.0000',
            'total_installments' => 3,
            'currency' => 'MXN',
            'start_date' => now()->toDateString(),
            'payment_day' => now()->day,
        ]
    );

    $this->debt = Debt::where('name', 'Test Debt')->first();
});

it('pays an installment and confirms the linked transaction', function () {
    $installment = $this->debt->installments()->orderBy('installment_number')->first();

    $response = $this->actingAs($this->user)->patchJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts/{$this->debt->id}/installments/{$installment->id}/pay"
    );

    $response->assertSuccessful()
        ->assertJsonPath('data.installment_number', 1);

    $installment->refresh();
    expect($installment->paid_at)->not->toBeNull();

    $this->debt->refresh();
    expect($this->debt->paid_installments)->toBe(1);

    if ($installment->transaction_id) {
        $transaction = Transaction::find($installment->transaction_id);
        expect($transaction->confirmed_at)->not->toBeNull();
    }
});

it('prevents paying an already paid installment', function () {
    $installment = $this->debt->installments()->orderBy('installment_number')->first();
    $installment->update(['paid_at' => now()]);

    $response = $this->actingAs($this->user)->patchJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts/{$this->debt->id}/installments/{$installment->id}/pay"
    );

    $response->assertStatus(422)
        ->assertJsonPath('message', 'Installment is already paid.');
});

it('accepts custom paid_at date', function () {
    $installment = $this->debt->installments()->orderBy('installment_number')->first();

    $response = $this->actingAs($this->user)->patchJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts/{$this->debt->id}/installments/{$installment->id}/pay",
        ['paid_at' => '2026-03-10T10:00:00Z']
    );

    $response->assertSuccessful();

    $installment->refresh();
    expect($installment->paid_at)->not->toBeNull();
});

it('lists upcoming installments within default 30 days', function () {
    $response = $this->actingAs($this->user)->getJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts/upcoming"
    );

    $response->assertSuccessful();

    $data = $response->json('data');
    foreach ($data as $item) {
        expect($item['paid_at'])->toBeNull();
    }
});

it('lists upcoming installments with custom days parameter', function () {
    $response = $this->actingAs($this->user)->getJson(
        "/api/v1/workspaces/{$this->workspace->id}/debts/upcoming?days=365"
    );

    $response->assertSuccessful();
    expect($response->json('data'))->toHaveCount(3);
});

it('installment numbers are sequential starting from 1', function () {
    $installments = $this->debt->installments()->orderBy('installment_number')->get();

    expect($installments)->toHaveCount(3);
    expect($installments[0]->installment_number)->toBe(1);
    expect($installments[1]->installment_number)->toBe(2);
    expect($installments[2]->installment_number)->toBe(3);
});

it('each installment has a linked projected transaction', function () {
    $installments = $this->debt->installments;

    foreach ($installments as $installment) {
        expect($installment->transaction_id)->not->toBeNull();

        $transaction = Transaction::find($installment->transaction_id);
        expect($transaction)->not->toBeNull();
        expect($transaction->type)->toBe('expense');
        expect($transaction->confirmed_at)->toBeNull();
        expect($transaction->category)->toBe('debt_payment');
    }
});
