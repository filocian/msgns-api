<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\BulkAssignRoles;

use Src\Shared\Core\Bus\Command;

/**
 * Command to assign roles to multiple users in bulk.
 * Uses REPLACE semantics - the submitted set becomes the user's complete role set.
 */
final readonly class BulkAssignRolesCommand implements Command
{
    /**
     * @param array<int> $userIds
     * @param array<string> $roles
     */
    public function __construct(
        public array $userIds,
        public array $roles,
    ) {}

    public function commandName(): string
    {
        return 'identity.bulk_assign_roles';
    }
}
