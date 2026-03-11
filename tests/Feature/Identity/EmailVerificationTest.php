<?php

declare(strict_types=1);



it('verifies email with valid token', function () {
    $user = $this->create_user(['email' => 'user@example.com', 'email_verified_at' => null]);

    // Generate token via infrastructure class (needs app bootstrap)
    $tokenService = app(\Src\Identity\Domain\Ports\VerificationTokenPort::class);
    $token = $tokenService->generate('user@example.com');

    $response = $this->postWithHeaders('/api/v2/identity/email/verify', [
        'token' => $token,
    ]);
    $response->assertStatus(200);
});

it('returns 422 for invalid token', function () {
    $response = $this->postWithHeaders('/api/v2/identity/email/verify', [
        'token' => 'invalid-token',
    ]);
    $response->assertStatus(422);
});

it('requests verification email', function () {
    $this->create_user(['email' => 'user@example.com', 'email_verified_at' => null]);

    $response = $this->postWithHeaders('/api/v2/identity/email/request-verification', [
        'email' => 'user@example.com',
    ]);
    $response->assertStatus(200);
});
