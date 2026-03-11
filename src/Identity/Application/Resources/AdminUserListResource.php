<?php
declare(strict_types=1);
namespace Src\Identity\Application\Resources;
final readonly class AdminUserListResource
{
    public function __construct(
        public int $id,
        public string $email,
        public string $name,
        public bool $active,
        public bool $emailVerified,
        public string $createdAt,
    ) {}
}
