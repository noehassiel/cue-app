<?php

use App\Models\User;

it('logs out and revokes the current token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;

    $response = $this->withToken($token)->postJson('/api/v1/auth/logout');

    $response->assertSuccessful()
        ->assertJson(['message' => 'Logged out']);

    expect($user->tokens()->count())->toBe(0);
});

it('returns 401 when logging out without a token', function () {
    $response = $this->postJson('/api/v1/auth/logout');

    $response->assertUnauthorized();
});
