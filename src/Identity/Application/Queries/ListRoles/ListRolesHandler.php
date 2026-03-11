<?php

declare(strict_types=1);

namespace Src\Identity\Application\Queries\ListRoles;

use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Application\Resources\RoleResource;

final class ListRolesHandler implements QueryHandler
{
    public function __construct(
        private readonly RolePort $roles,
    ) {}

    /** @return RoleResource[] */
    public function handle(Query $query): array
    {
        assert($query instanceof ListRolesQuery);

        $roleDatas = $this->roles->listRoles();

        return array_map(
            fn($r) => new RoleResource($r->id, $r->name, $r->permissions, $r->usersCount),
            $roleDatas,
        );
    }
}
