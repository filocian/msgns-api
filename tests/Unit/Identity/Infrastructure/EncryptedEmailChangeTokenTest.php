<?php

declare(strict_types=1);

use Carbon\Carbon;
use Src\Identity\Infrastructure\Auth\EncryptedEmailChangeToken;
use Src\Identity\Infrastructure\Auth\EncryptedVerificationToken;
use Src\Shared\Core\Errors\ValidationFailed;

it('generates and validates an email change token', function () {
    $tokenService = new EncryptedEmailChangeToken();
    $token = $tokenService->generate(42, 'new@example.com');

    expect($token)->toBeString()->not->toBeEmpty();

    $result = $tokenService->validate($token);
    expect($result)->toBe(['userId' => 42, 'newEmail' => 'new@example.com']);
});

it('throws on tampered token', function () {
    $tokenService = new EncryptedEmailChangeToken();
    $tokenService->validate('tampered-invalid-token');
})->throws(ValidationFailed::class, 'invalid_or_expired_token');

it('throws on expired token', function () {
    $tokenService = new EncryptedEmailChangeToken();
    $token = $tokenService->generate(1, 'new@example.com');

    // Time-travel 61 minutes into the future
    Carbon::setTestNow(Carbon::now()->addMinutes(61));

    try {
        $tokenService->validate($token);
    } finally {
        Carbon::setTestNow();
    }
})->throws(ValidationFailed::class, 'invalid_or_expired_token');

it('rejects a verification token used as email change token', function () {
    $verificationService = new EncryptedVerificationToken();
    $emailChangeService = new EncryptedEmailChangeToken();

    $verificationToken = $verificationService->generate('user@example.com');
    $emailChangeService->validate($verificationToken);
})->throws(ValidationFailed::class, 'invalid_or_expired_token');

it('rejects an email change token used as verification token', function () {
    $emailChangeService = new EncryptedEmailChangeToken();
    $verificationService = new EncryptedVerificationToken();

    $emailChangeToken = $emailChangeService->generate(1, 'new@example.com');
    $verificationService->validate($emailChangeToken);
})->throws(ValidationFailed::class, 'invalid_or_expired_token');
