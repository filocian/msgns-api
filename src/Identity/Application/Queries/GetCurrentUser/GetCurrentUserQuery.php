<?php

declare(strict_types=1);

namespace Src\Identity\Application\Queries\GetCurrentUser;

use Src\Shared\Core\Bus\Query;

final class GetCurrentUserQuery implements Query
{
    public function __construct(public readonly int $userId) {}

    public function queryName(): string
    {
        return 'identity.get_current_user';
    }
}
