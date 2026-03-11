<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Ports;

use Src\Shared\Core\Bus\PaginatedResult;
use Src\Identity\Domain\Entities\IdentityUser;

interface IdentityUserRepository
{
    public function findById(int $id): ?IdentityUser;
    public function findByEmail(string $email): ?IdentityUser;
    public function findByGoogleId(string $googleId): ?IdentityUser;
    public function save(IdentityUser $user): IdentityUser;
    /** @param array{page?: int, perPage?: int, sortBy?: string, sortDir?: string, search?: string|null, active?: bool|null, role?: string|null} $filters */
    public function list(array $filters): PaginatedResult;
}
