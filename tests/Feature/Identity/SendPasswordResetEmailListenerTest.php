<?php

declare(strict_types=1);

use Src\Shared\Core\Ports\MailPort;
use Illuminate\Support\Facades\Log;
use Resend\Exceptions\ErrorException;
use Src\Identity\Domain\Events\PasswordResetRequested;
use Src\Identity\Infrastructure\Listeners\SendPasswordResetEmail;

it('sends password reset email with correct template and variables on success', function () {
    $capturedArgs = [];

    $mailPort = Mockery::mock(MailPort::class);
    $mailPort
        ->shouldReceive('send')
        ->once()
        ->withArgs(function (string $to, string $subject, string $html) use (&$capturedArgs) {
            $capturedArgs = compact('to', 'subject', 'html');
            return true;
        });

    $event = new PasswordResetRequested(
        email: 'user@example.com',
        token: 'test-reset-token-123',
    );

    $listener = new SendPasswordResetEmail($mailPort);
    $listener->handle($event);

    // Correct recipient
    expect($capturedArgs['to'])->toBe('user@example.com');

    // HTML contains the reset token in the link
    expect($capturedArgs['html'])->toContain('test-reset-token-123');

    // HTML contains the password-reset path (from the blade template)
    expect($capturedArgs['html'])->toContain('/password-reset/');
});

it('logs error and does not rethrow when MailPort throws ErrorException', function () {
    $mailPort = Mockery::mock(MailPort::class);
    $mailPort
        ->shouldReceive('send')
        ->once()
        ->andThrow(new ErrorException([
            'message' => 'invalid_from_address',
            'name'    => 'invalid_from_address',
            'statusCode' => 422,
        ]));

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return $message === 'Failed to send password reset email'
                && $context['email'] === 'user@example.com'
                && str_contains($context['error'], 'invalid_from_address');
        });

    $event = new PasswordResetRequested(
        email: 'user@example.com',
        token: 'any-token',
    );

    $listener = new SendPasswordResetEmail($mailPort);

    // Must NOT throw — failure is non-blocking
    expect(fn () => $listener->handle($event))->not->toThrow(\Throwable::class);
});

afterEach(fn () => Mockery::close());
