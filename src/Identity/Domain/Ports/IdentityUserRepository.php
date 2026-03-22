<?php

declare(strict_types=1);

namespace Src\Identity\Domain\Ports;

use Src\Shared\Core\Bus\PaginatedResult;
use Src\Identity\Domain\Entities\IdentityUser;

interface IdentityUserRepository
{
    public function findById(int $id): ?IdentityUser;
    public function findByEmail(string $email): ?IdentityUser;
    public function findByPendingEmail(string $email): ?IdentityUser;
    public function findByGoogleId(string $googleId): ?IdentityUser;
    public function save(IdentityUser $user): IdentityUser;
    public function applySignUpSideEffects(int $userId, ?string $userAgent): void;
    public function applyLoginSideEffects(int $userId, ?string $userAgent): void;
    /** @param array{page?: int, perPage?: int, sortBy?: string, sortDir?: string, search?: string|null, active?: bool|null, role?: string|null} $filters */
    public function list(array $filters): PaginatedResult;

    /**
     * @param array{
     *     search?: string|null,
     *     active?: bool|null,
     *     role?: string|null,
     *     created_from?: string|null,
     *     created_to?: string|null,
     * } $filters
     * @return iterable<int, \App\Models\User>
     */
    public function export(array $filters): iterable;

    /**
     * Execute the given callable inside a database transaction.
     *
     * If the callable throws an exception, all writes performed within it
     * are rolled back and the exception is re-thrown to the caller.
     *
     * @template T
     * @param callable(): T $fn
     * @return T
     */
    public function inTransaction(callable $fn): mixed;
}
