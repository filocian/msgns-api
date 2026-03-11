<?php
declare(strict_types=1);
namespace Src\Identity\Application\Resources;
final readonly class PermissionResource
{
    public function __construct(
        public int $id,
        public string $name,
    ) {}
}
