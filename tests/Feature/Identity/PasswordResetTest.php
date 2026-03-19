<?php

declare(strict_types=1);



it('requests password reset silently', function () {
    $response = $this->postWithHeaders('/api/v2/identity/password/request-reset', [
        'email' => 'nobody@example.com',
    ]);
    $response->assertStatus(200);
});

it('resets password with valid token', function () {
    $this->create_user(['email' => 'user@example.com']);

    $tokenService = app(\Src\Identity\Domain\Ports\PasswordResetTokenPort::class);
    $token = $tokenService->generate('user@example.com');

    $response = $this->postWithHeaders('/api/v2/identity/password/reset', [
        'token'           => $token,
        'password'        => 'NewPass123!',
        'repeat_password' => 'NewPass123!',
    ]);
    $response->assertStatus(200);
});

it('returns 422 for invalid reset token', function () {
    $response = $this->postWithHeaders('/api/v2/identity/password/reset', [
        'token'           => 'bad-token',
        'password'        => 'NewPass123!',
        'repeat_password' => 'NewPass123!',
    ]);
    $response->assertStatus(422);
});

it('clears password_reset_required flag on successful reset', function () {
    $user = $this->create_user([
        'email'                  => 'reset-required@example.com',
        'password_reset_required' => true,
    ]);

    $tokenService = app(\Src\Identity\Domain\Ports\PasswordResetTokenPort::class);
    $token = $tokenService->generate('reset-required@example.com');

    $response = $this->postWithHeaders('/api/v2/identity/password/reset', [
        'token'           => $token,
        'password'        => 'NewPass123!',
        'repeat_password' => 'NewPass123!',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('users', [
        'email'                  => 'reset-required@example.com',
        'password_reset_required' => false,
    ]);
});
