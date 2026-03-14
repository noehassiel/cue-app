<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns the plans list unauthenticated', function () {
    $response = $this->getJson('/api/v1/billing/plans');

    $response->assertUnauthorized();
});

it('returns the plans list when authenticated', function () {
    $response = $this->actingAs($this->user)->getJson('/api/v1/billing/plans');

    $response->assertSuccessful();

    $data = $response->json('data');
    expect($data)->toBeArray()->toHaveCount(3);
    expect($data[0]['id'])->toBe('free');
    expect($data[0]['price'])->toBe(0);
});

it('returns subscription status for a free user', function () {
    $response = $this->actingAs($this->user)->getJson('/api/v1/billing/status');

    $response->assertSuccessful()
        ->assertJsonPath('data.subscribed', false)
        ->assertJsonPath('data.plan', 'free');
});

it('returns 422 when checkout product_id is missing', function () {
    $response = $this->actingAs($this->user)->postJson('/api/v1/billing/checkout', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['product_id']);
});

it('returns 422 when checkout success_url is invalid', function () {
    $response = $this->actingAs($this->user)->postJson('/api/v1/billing/checkout', [
        'product_id' => 'prod_123',
        'success_url' => 'not-a-url',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['success_url']);
});

it('returns 401 when portal is accessed unauthenticated', function () {
    $response = $this->getJson('/api/v1/billing/portal');

    $response->assertUnauthorized();
});
