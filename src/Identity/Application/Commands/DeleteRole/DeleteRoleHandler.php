<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\DeleteRole;

use Src\Shared\Core\Bus\Command;
use Src\Shared\Core\Bus\CommandHandler;
use Src\Identity\Domain\Ports\RolePort;

final class DeleteRoleHandler implements CommandHandler
{
    public function __construct(
        private readonly RolePort $roles,
    ) {}

    public function handle(Command $command): mixed
    {
        assert($command instanceof DeleteRoleCommand);

        $this->roles->deleteRole($command->id);

        return null;
    }
}
