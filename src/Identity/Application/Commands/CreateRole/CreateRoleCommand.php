<?php

declare(strict_types=1);

namespace Src\Identity\Application\Commands\CreateRole;

use Src\Shared\Core\Bus\Command;

final class CreateRoleCommand implements Command
{
    public function __construct(
        public readonly string $name,
    ) {}

    public function commandName(): string
    {
        return 'identity.create_role';
    }
}
