<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Src\Identity\Domain\Events\VerificationRequested;
use Src\Shared\Core\Ports\MailPort;

final class SendVerificationEmailOnRequest
{
    public function __construct(
        private readonly MailPort $mailPort,
    ) {}

    public function handle(VerificationRequested $event): void
    {
        try {
            $html = view('emails.email-verification')
                ->with('verificationToken', $event->token)
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
