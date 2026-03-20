<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Src\Identity\Domain\Events\EmailChangeRequested;
use Src\Shared\Core\Ports\MailPort;

final class SendEmailChangeVerification
{
    public function __construct(
        private readonly MailPort $mailPort,
    ) {}

    public function handle(EmailChangeRequested $event): void
    {
        try {
            $html = view('emails.email-change-verification')
                ->with('token', $event->token)
                ->with('currentEmail', $event->currentEmail)
                ->with('newEmail', $event->newEmail)
                ->render();

            $this->mailPort->send(
                $event->newEmail,
                __('emailChange.subject'),
                $html,
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send email change verification', [
                'userId'   => $event->userId,
                'newEmail' => $event->newEmail,
                'error'    => $e->getMessage(),
            ]);
        }
    }
}
