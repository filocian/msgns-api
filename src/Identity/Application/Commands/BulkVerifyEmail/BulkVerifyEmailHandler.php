<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\BulkVerifyEmail;

use Src\Identity\Application\Resources\BulkActionResultResource;
use Src\Identity\Application\Resources\BulkSummary;
use Src\Identity\Application\Resources\BulkUserResult;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;

/**
 * Handler for bulk email verification.
 * Continue-on-failure semantics: processes all users and reports per-user status.
 */
final class BulkVerifyEmailHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
    ) {}

    public function handle(Command $command): BulkActionResultResource
    {
        assert($command instanceof BulkVerifyEmailCommand);

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

            // Check if already verified
            if ($user->emailVerifiedAt !== null) {
                $results[] = new BulkUserResult(
                    userId: $userId,
                    status: 'unchanged',
                    message: 'Email already verified',
                );
                $succeeded++;
                continue;
            }

            // Verify the email
            $user->forceVerifyEmail();
            $this->repo->save($user);

            $results[] = new BulkUserResult(
                userId: $userId,
                status: 'updated',
                message: 'Email verified successfully',
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
