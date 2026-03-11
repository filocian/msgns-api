<?php

declare(strict_types=1);



it('logs in with valid credentials', function () {
    $this->create_user(['email' => 'user@example.com', 'password' => 'Pass123456!']);

    $response = $this->postWithHeaders('/api/v2/identity/login', [
        'email'    => 'user@example.com',
        'password' => 'Pass123456!',
    ]);
    $response->assertStatus(200)
             ->assertJsonPath('data.user.email', 'user@example.com');
});

it('returns 422 for invalid credentials', function () {
    $this->create_user(['email' => 'user@example.com', 'password' => 'Pass123456!']);

    $response = $this->postWithHeaders('/api/v2/identity/login', [
        'email'    => 'user@example.com',
        'password' => 'WrongPassword!',
    ]);
    $response->assertStatus(422);
});

it('returns 422 for non-existent user', function () {
    $response = $this->postWithHeaders('/api/v2/identity/login', [
        'email'    => 'nobody@example.com',
        'password' => 'Pass123!',
    ]);
    $response->assertStatus(422);
});
