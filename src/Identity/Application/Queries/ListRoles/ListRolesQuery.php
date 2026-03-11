<?php

declare(strict_types=1);

namespace Src\Identity\Application\Queries\ListRoles;

use Src\Shared\Core\Bus\Query;

final class ListRolesQuery implements Query
{
    public function queryName(): string
    {
        return 'identity.list_roles';
    }
}
