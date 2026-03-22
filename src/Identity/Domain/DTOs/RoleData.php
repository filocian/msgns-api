<?php

declare(strict_types=1);

namespace Src\Identity\Domain\DTOs;

final readonly class RoleData
{
    /** @param string[] $permissions */
    public function __construct(
        public int $id,
        public string $name,
        public array $permissions,
        public int $usersCount,
    ) {}
}
