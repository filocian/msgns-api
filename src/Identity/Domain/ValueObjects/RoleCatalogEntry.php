<?php

declare(strict_types=1);

namespace Src\Identity\Domain\ValueObjects;

final readonly class RoleCatalogEntry
{
    /** @param string[] $permissions */
    public function __construct(
        public string $name,
        public array $permissions,
        public bool $isCore,
    ) {}
}
