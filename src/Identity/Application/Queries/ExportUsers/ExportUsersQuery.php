<?php

declare(strict_types=1);

namespace Src\Identity\Application\Queries\ExportUsers;

use Src\Shared\Core\Bus\Query;

final class ExportUsersQuery implements Query
{
    public function __construct(
        public readonly ?string $search = null,
        public readonly ?bool $active = null,
        public readonly ?string $role = null,
        public readonly ?string $createdFrom = null,
        public readonly ?string $createdTo = null,
    ) {}

    public function queryName(): string
    {
        return 'identity.export_users';
    }
}
