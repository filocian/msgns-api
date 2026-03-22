<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\BulkChangeEmail;

use Src\Identity\Application\Resources\BulkActionResultResource;
use Src\Identity\Application\Resources\BulkSummary;
use Src\Identity\Application\Resources\BulkUserResult;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\ValidationFailed;

/**
 * Handler for bulk email change.
 * ATOMIC semantics: validates all preconditions, then executes in a transaction.
 * Any failure rolls back the entire request.
 */
final class BulkChangeEmailHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
    ) {}

    public function handle(Command $command): BulkActionResultResource
    {
        assert($command instanceof BulkChangeEmailCommand);

        // Validate request-level invariants before transaction
        $this->validateRequestLevelInvariants($command->changes);

        // Execute within transaction
        return $this->repo->inTransaction(function () use ($command): BulkActionResultResource {
            $results = [];
            $succeeded = 0;

            foreach ($command->changes as $userId => $newEmail) {
                $user = $this->repo->findById((int) $userId);

                if ($user === null) {
                    throw ValidationFailed::because('user_not_found', ['user_id' => $userId]);
                }

                // Check for duplicate in DB (email or pending_email)
                $existingByEmail = $this->repo->findByEmail($newEmail);
                $existingByPending = $this->repo->findByPendingEmail($newEmail);

                if (($existingByEmail !== null && $existingByEmail->id !== (int) $userId) ||
                    ($existingByPending !== null && $existingByPending->id !== (int) $userId)) {
                    throw ValidationFailed::because('email_already_exists', ['email' => $newEmail]);
                }

                // Check if email is unchanged
                if (strtolower(trim($user->email)) === $newEmail) {
                    $results[] = new BulkUserResult(
                        userId: (int) $userId,
                        status: 'unchanged',
                        message: 'Email is the same as current',
                    );
                    $succeeded++;
                    continue;
                }

                // Change the email
                $user->changeEmail($newEmail);
                $this->repo->save($user);

                $results[] = new BulkUserResult(
                    userId: (int) $userId,
                    status: 'updated',
                    message: 'Email changed successfully',
                );
                $succeeded++;
            }

            return new BulkActionResultResource(
                summary: new BulkSummary(
                    requested: count($command->changes),
                    succeeded: $succeeded,
                    failed: 0,
                ),
                results: $results,
            );
        });
    }

    /**
     * Validate request-level invariants before starting transaction.
     *
     * @param array<int, string> $changes
     * @throws ValidationFailed
     */
    private function validateRequestLevelInvariants(array $changes): void
    {
        // Check for duplicate emails in the request
        $emails = array_values($changes);
        $uniqueEmails = array_unique($emails);
        if (count($emails) !== count($uniqueEmails)) {
            throw ValidationFailed::because('duplicate_emails_in_request');
        }

        // Check for duplicate user IDs in the request
        $userIds = array_keys($changes);
        $uniqueUserIds = array_unique($userIds);
        if (count($userIds) !== count($uniqueUserIds)) {
            throw ValidationFailed::because('duplicate_user_ids_in_request');
        }
    }
}
