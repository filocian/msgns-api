<?php

declare(strict_types=1);

namespace Src\Identity\Application\Queries\ListUsers;

use Src\Shared\Core\Bus\Query;

final class ListUsersQuery implements Query
{
    public function __construct(
        public readonly int $page = 1,
        public readonly int $perPage = 15,
        public readonly string $sortBy = 'created_at',
        public readonly string $sortDir = 'desc',
        public readonly ?string $search = null,
        public readonly ?bool $active = null,
        public readonly ?string $role = null,
    ) {}

    public function queryName(): string
    {
        return 'identity.list_users';
    }
}
