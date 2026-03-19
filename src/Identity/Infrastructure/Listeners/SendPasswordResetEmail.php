<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Src\Identity\Domain\Events\PasswordResetRequested;
use Src\Shared\Core\Ports\MailPort;

final class SendPasswordResetEmail
{
    public function __construct(
        private readonly MailPort $mailPort,
    ) {}

    public function handle(PasswordResetRequested $event): void
    {
        try {
            $html = view('emails.reset-password')
                ->with('email', $event->email)
                ->with('verificationToken', $event->token)
                ->render();

            $this->mailPort->send($event->email, __('passwordReset.subject'), $html);
        } catch (\Throwable $e) {
            Log::error('Failed to send password reset email', [
                'email' => $event->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
