<?php

declare(strict_types=1);

namespace Src\Identity\Domain\DTOs;

final readonly class PermissionData
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}
