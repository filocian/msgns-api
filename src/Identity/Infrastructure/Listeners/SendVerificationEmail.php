<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Listeners;

use Src\Identity\Domain\Events\UserRegistered;
use Src\Identity\Domain\Ports\VerificationTokenPort;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Illuminate\Support\Facades\Log;

final class SendVerificationEmail
{
    public function __construct(
        private readonly VerificationTokenPort $tokenPort,
        private readonly IdentityUserRepository $repo,
    ) {}

    public function handle(UserRegistered $event): void
    {
        try {
            $user = $this->repo->findById($event->userId);
            if (!$user) return;
            // Token generated; actual email sending deferred to a job/mail system
            $token = $this->tokenPort->generate($user->email);
            Log::info('Verification email token generated', ['user_id' => $event->userId, 'token_length' => strlen($token)]);
        } catch (\Throwable $e) {
            Log::error('Failed to generate verification token', ['user_id' => $event->userId, 'error' => $e->getMessage()]);
        }
    }
}
