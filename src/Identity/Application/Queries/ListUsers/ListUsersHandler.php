<?php

declare(strict_types=1);

namespace Src\Identity\Application\Queries\ListUsers;

use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Shared\Core\Bus\PaginatedResult;
use Src\Identity\Domain\Ports\IdentityUserRepository;

final class ListUsersHandler implements QueryHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
    ) {}

    public function handle(Query $query): PaginatedResult
    {
        assert($query instanceof ListUsersQuery);

        $paginated = $this->repo->list([
            'page'    => $query->page,
            'perPage' => $query->perPage,
            'sortBy'  => $query->sortBy,
            'sortDir' => $query->sortDir,
            'search'  => $query->search,
            'active'  => $query->active,
            'role'    => $query->role,
        ]);

        return $paginated;
    }
}
