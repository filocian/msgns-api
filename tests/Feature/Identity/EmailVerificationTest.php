<?php

declare(strict_types=1);

use Src\Shared\Core\Ports\MailPort;

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

it('sends verification email when user registers', function () {
    $capturedArgs = [];

    $mailPort = Mockery::mock(MailPort::class);
    $mailPort
        ->shouldReceive('send')
        ->once()
        ->withArgs(function (string $to, string $subject, string $html) use (&$capturedArgs) {
            $capturedArgs = compact('to', 'subject', 'html');
            return true;
        });

    $this->app->instance(MailPort::class, $mailPort);

    $response = $this->postWithHeaders('/api/v2/identity/sign-up', [
        'email'           => 'newuser@example.com',
        'name'            => 'New User',
        'password'        => 'Pass123!',
        'repeat_password' => 'Pass123!',
    ]);

    $response->assertStatus(200);

    expect($capturedArgs['to'])->toBe('newuser@example.com');
    expect($capturedArgs['html'])->not->toBeEmpty();
})->after(fn () => Mockery::close());

it('sends verification email on manual re-request', function () {
    $capturedArgs = [];

    $this->create_user(['email' => 'unverified@example.com', 'email_verified_at' => null]);

    $mailPort = Mockery::mock(MailPort::class);
    $mailPort
        ->shouldReceive('send')
        ->once()
        ->withArgs(function (string $to, string $subject, string $html) use (&$capturedArgs) {
            $capturedArgs = compact('to', 'subject', 'html');
            return true;
        });

    $this->app->instance(MailPort::class, $mailPort);

    $response = $this->postWithHeaders('/api/v2/identity/email/request-verification', [
        'email' => 'unverified@example.com',
    ]);

    $response->assertStatus(200);

    expect($capturedArgs['to'])->toBe('unverified@example.com');
    expect($capturedArgs['html'])->not->toBeEmpty();
})->after(fn () => Mockery::close());

it('returns 422 email_already_verified when already-verified user requests re-send', function () {
    $this->create_user([
        'email'             => 'verified@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->postWithHeaders('/api/v2/identity/email/request-verification', [
        'email' => 'verified@example.com',
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('error.code', 'email_already_verified');
});

it('returns 422 email_already_verified when already-verified user tries to verify again', function () {
    $this->create_user([
        'email'             => 'alreadyverified@example.com',
        'email_verified_at' => now(),
    ]);

    $tokenService = app(\Src\Identity\Domain\Ports\VerificationTokenPort::class);
    $token = $tokenService->generate('alreadyverified@example.com');

    $response = $this->postWithHeaders('/api/v2/identity/email/verify', [
        'token' => $token,
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('error.code', 'email_already_verified');
});
