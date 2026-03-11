<?php

declare(strict_types=1);

use Src\Identity\Infrastructure\Auth\EncryptedVerificationToken;
use Src\Shared\Core\Errors\ValidationFailed;

it('generates and validates a verification token', function () {
    $tokenService = new EncryptedVerificationToken();
    $email = 'user@example.com';
    $token = $tokenService->generate($email);
    $result = $tokenService->validate($token);
    expect($result)->toBe($email);
});

it('throws on invalid token', function () {
    $tokenService = new EncryptedVerificationToken();
    $tokenService->validate('invalid-token');
})->throws(ValidationFailed::class);
