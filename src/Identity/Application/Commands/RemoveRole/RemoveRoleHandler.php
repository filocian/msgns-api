<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\RemoveRole;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Identity\Domain\Ports\IdentityUserRepository;
use Src\Identity\Domain\Ports\RolePort;

final class RemoveRoleHandler implements CommandHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
        private readonly RolePort $roles,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof RemoveRoleCommand);

        $user = $this->repo->findById($command->userId);
        if ($user === null) {
            throw NotFound::because('user_not_found');
        }

        $this->roles->removeRole($command->userId, $command->role);

        return null;
    }
}
