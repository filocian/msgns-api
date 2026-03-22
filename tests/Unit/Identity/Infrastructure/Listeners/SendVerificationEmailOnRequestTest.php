<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Resend\Exceptions\ErrorException;
use Src\Identity\Domain\Events\VerificationRequested;
use Src\Identity\Infrastructure\Listeners\SendVerificationEmailOnRequest;
use Src\Shared\Core\Ports\MailPort;

it('renders blade and sends email using event token on VerificationRequested', function () {
    $capturedArgs = [];

    $mailPort = Mockery::mock(MailPort::class);
    $mailPort
        ->shouldReceive('send')
        ->once()
        ->withArgs(function (string $to, string $subject, string $html) use (&$capturedArgs) {
            $capturedArgs = compact('to', 'subject', 'html');
            return true;
        });

    $event = new VerificationRequested(email: 'user@example.com', token: 'test-verification-token-456');

    $listener = new SendVerificationEmailOnRequest($mailPort);
    $listener->handle($event);

    expect($capturedArgs['to'])->toBe('user@example.com');
    expect($capturedArgs['html'])->toContain('test-verification-token-456');
});

it('logs error and does not rethrow when MailPort throws ErrorException', function () {
    $mailPort = Mockery::mock(MailPort::class);
    $mailPort
        ->shouldReceive('send')
        ->once()
        ->andThrow(new ErrorException([
            'message'    => 'invalid_from_address',
            'name'       => 'invalid_from_address',
            'statusCode' => 422,
        ]));

    Log::shouldReceive('error')
        ->once()
        ->withArgs(function (string $message, array $context) {
            return $message === 'Failed to send verification email'
                && $context['email'] === 'user@example.com'
                && str_contains($context['error'], 'invalid_from_address');
        });

    $event = new VerificationRequested(email: 'user@example.com', token: 'any-token');

    $listener = new SendVerificationEmailOnRequest($mailPort);

    // Must NOT throw — failure is non-blocking
    expect(fn () => $listener->handle($event))->not->toThrow(\Throwable::class);
});

afterEach(fn () => Mockery::close());
