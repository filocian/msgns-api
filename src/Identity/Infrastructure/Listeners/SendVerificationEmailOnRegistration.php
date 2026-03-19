<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Src\Identity\Domain\Events\UserRegistered;
use Src\Identity\Domain\Ports\VerificationTokenPort;
use Src\Shared\Core\Ports\MailPort;

final class SendVerificationEmailOnRegistration
{
    public function __construct(
        private readonly VerificationTokenPort $tokenPort,
        private readonly MailPort $mailPort,
    ) {}

    public function handle(UserRegistered $event): void
    {
        try {
            $token = $this->tokenPort->generate($event->email);

            $html = view('emails.email-verification')
                ->with('verificationToken', $token)
                ->render();

            $this->mailPort->send($event->email, __('emailVerification.subject'), $html);
        } catch (\Throwable $e) {
            Log::error('Failed to send verification email', [
                'email' => $event->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
