<?php

declare(strict_types=1);



it('signs up a new user successfully', function () {
    $response = $this->postWithHeaders('/api/v2/identity/sign-up', [
        'email'           => 'new@example.com',
        'name'            => 'New User',
        'password'        => 'Pass123!',
        'repeat_password' => 'Pass123!',
    ]);
    $response->assertStatus(201)
             ->assertJsonPath('data.email', 'new@example.com');
});

it('returns 422 for duplicate email', function () {
    $this->create_user(['email' => 'existing@example.com']);

    $response = $this->postWithHeaders('/api/v2/identity/sign-up', [
        'email'           => 'existing@example.com',
        'name'            => 'Another User',
        'password'        => 'Pass123!',
        'repeat_password' => 'Pass123!',
    ]);
    $response->assertStatus(422);
});

it('returns 400 for missing required fields', function () {
    $response = $this->postWithHeaders('/api/v2/identity/sign-up', []);
    $response->assertStatus(400);
});

it('returns 400 for password mismatch', function () {
    $response = $this->postWithHeaders('/api/v2/identity/sign-up', [
        'email'           => 'new@example.com',
        'name'            => 'New User',
        'password'        => 'Pass123!',
        'repeat_password' => 'Different123!',
    ]);
    $response->assertStatus(400);
});
