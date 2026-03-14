<?php

use App\Models\User;

it('logs in with valid credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'iPhone 15 Pro',
    ]);

    $response->assertSuccessful()
        ->assertJsonStructure([
            'data' => ['user' => ['id', 'email'], 'token'],
        ]);
});

it('fails login with wrong password', function () {
    $user = User::factory()->create(['password' => bcrypt('password')]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
        'device_name' => 'iPhone 15 Pro',
    ]);

    $response->assertStatus(401);
});

it('fails login without device_name', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['device_name']);
});
