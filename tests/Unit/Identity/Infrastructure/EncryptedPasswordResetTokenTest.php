<?php

declare(strict_types=1);

use Src\Identity\Infrastructure\Auth\EncryptedPasswordResetToken;
use Src\Shared\Core\Errors\ValidationFailed;

it('generates and validates a password reset token', function () {
    $tokenService = new EncryptedPasswordResetToken();
    $email = 'user@example.com';
    $token = $tokenService->generate($email);
    $result = $tokenService->validate($token);
    expect($result)->toBe($email);
});

it('throws on invalid token', function () {
    $tokenService = new EncryptedPasswordResetToken();
    $tokenService->validate('invalid-token');
})->throws(ValidationFailed::class);
