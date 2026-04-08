<?php

declare(strict_types=1);

namespace Src\Identity\Application\Queries\ListPermissions;

use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Identity\Domain\Ports\RolePort;
use Src\Identity\Application\Resources\PermissionResource;

final class ListPermissionsHandler implements QueryHandler
{
    public function __construct(
        private readonly RolePort $roles,
    ) {}

    /** @return PermissionResource[] */
    public function handle(Query $query): array
    {
        assert($query instanceof ListPermissionsQuery);

        $permDatas = $this->roles->listPermissions();
        $descriptions = DomainPermissions::descriptions();

        return array_map(
            fn($p) => new PermissionResource(
                id: $p->id,
                name: $p->name,
                description: $descriptions[$p->name] ?? null,
            ),
            $permDatas,
        );
    }
}
