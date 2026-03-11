<?php

declare(strict_types=1);

namespace Src\Identity\Application\Queries\ListPermissions;

use Src\Shared\Core\Bus\Query;

final class ListPermissionsQuery implements Query
{
    public function queryName(): string
    {
        return 'identity.list_permissions';
    }
}
