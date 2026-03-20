<?php

declare(strict_types=1);

namespace Src\Identity\Application\Queries\ExportUsers;

use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Identity\Domain\Ports\IdentityUserRepository;

final class ExportUsersHandler implements QueryHandler
{
    public function __construct(
        private readonly IdentityUserRepository $repo,
    ) {}

    /**
     * @return iterable<int, \App\Models\User>
     */
    public function handle(Query $query): iterable
    {
        assert($query instanceof ExportUsersQuery);

        return $this->repo->export([
            'search'       => $query->search,
            'active'       => $query->active,
            'role'         => $query->role,
            'created_from' => $query->createdFrom,
            'created_to'   => $query->createdTo,
        ]);
    }
}
