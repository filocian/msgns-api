<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\BulkAssignRoles;

use Src\Identity\Application\Resources\BulkActionResultResource;
use Src\Identity\Application\Resources\BulkSummary;
use Src\Identity\Application\Resources\BulkUserResult;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\RolePort;
use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;

/**
 * Handler for bulk role assignment.
 * Continue-on-failure semantics: processes all users and reports per-user status.
 * Uses REPLACE semantics via RolePort::syncRoles().
 */
final class BulkAssignRolesHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly RolePort $rolePort,
    ) {}

    public function handle(Command $command): BulkActionResultResource
    {
        assert($command instanceof BulkAssignRolesCommand);

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

            // Get current roles to check for changes
            $currentRoles = $this->rolePort->getRolesForUser($userId);
            sort($currentRoles);
            $newRoles = $command->roles;
            sort($newRoles);

            // Check if roles are unchanged
            if ($currentRoles === $newRoles) {
                $results[] = new BulkUserResult(
                    userId: $userId,
                    status: 'unchanged',
                    message: 'Roles already match the requested set',
                );
                $succeeded++;
                continue;
            }

            // Replace roles using syncRoles
            $this->rolePort->syncRoles($userId, $command->roles);

            $results[] = new BulkUserResult(
                userId: $userId,
                status: 'updated',
                message: 'Roles updated successfully',
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
