<?php

use App\Models\User;
use App\Models\Workspace;

it('registers a new user and creates a default workspace', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'data' => ['user' => ['id', 'name', 'email'], 'token'],
        ]);

    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
    expect(Workspace::where('currency', 'MXN')->exists())->toBeTrue();
});

it('fails registration with invalid data', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'mismatch',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('fails registration with duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Test User',
        'email' => 'taken@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});
