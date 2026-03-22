<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\BulkPasswordReset;

use Src\Identity\Application\Resources\BulkActionResultResource;
use Src\Identity\Application\Resources\BulkSummary;
use Src\Identity\Application\Resources\BulkUserResult;
use Src\Identity\Domain\Events\PasswordResetRequested;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\PasswordResetTokenPort;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Bus\EventBus;

/**
 * Handler for bulk password reset.
 * Continue-on-failure semantics: processes all users and reports per-user status.
 * Reuses the standard password reset flow by publishing PasswordResetRequested events.
 */
final class BulkPasswordResetHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly PasswordResetTokenPort $tokenPort,
        private readonly EventBus $eventBus,
    ) {}

    public function handle(Command $command): BulkActionResultResource
    {
        assert($command instanceof BulkPasswordResetCommand);

        $results = [];
        $succeeded = 0;
        $failed = 0;

        foreach ($command->userIds as $userId) {
            $user = $this->repo->findById($userId);

            if ($user === null) {
                $results[] = new BulkUserResult(
                    userId: $userId,
                    status: 'failed',
                    code: 'user_not_found',
                    message: 'User not found',
                );
                $failed++;
                continue;
            }

            // Generate token and publish event (reuse standard flow)
            $token = $this->tokenPort->generate($user->email);
            $this->eventBus->publish(new PasswordResetRequested(
                email: $user->email,
                token: $token,
            ));

            $results[] = new BulkUserResult(
                userId: $userId,
                status: 'updated',
                message: 'Password reset requested',
            );
            $succeeded++;
        }

        return new BulkActionResultResource(
            summary: new BulkSummary(
                requested: count($command->userIds),
                succeeded: $succeeded,
                failed: $failed,
            ),
            results: $results,
        );
    }
}
