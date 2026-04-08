<?php

declare(strict_types=1);

namespace Src\Identity\Application\Queries\GetRole;

use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Application\Resources\RoleResource;

final class GetRoleHandler implements QueryHandler
{
    public function __construct(
        private readonly RolePort $roles,
    ) {}

    public function handle(Query $query): RoleResource
    {
        assert($query instanceof GetRoleQuery);

        // findById throws NotFound (→ 404) if not found
        $role = $this->roles->findById($query->id);

        return new RoleResource(
            id: $role->id,
            name: $role->name,
            permissions: $role->permissions,
            usersCount: $role->usersCount,
        );
    }
}
