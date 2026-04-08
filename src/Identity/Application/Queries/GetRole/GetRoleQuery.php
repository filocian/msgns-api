<?php

declare(strict_types=1);

namespace Src\Identity\Application\Queries\GetRole;

use Src\Shared\Core\Bus\Query;

final class GetRoleQuery implements Query
{
    public function __construct(
        public readonly int $id,
    ) {}

    public function queryName(): string
    {
        return 'identity.get_role';
    }
}
