<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Application\Queries\GetGoogleBusinessConnection;

use Src\GoogleBusiness\Domain\Models\UserGoogleBusinessConnection;
use Src\GoogleBusiness\Domain\Ports\GoogleBusinessConnectionRepositoryPort;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;

final class GetGoogleBusinessConnectionHandler implements QueryHandler
{
    public function __construct(
        private readonly GoogleBusinessConnectionRepositoryPort $repository,
    ) {}

    public function handle(Query $query): ?UserGoogleBusinessConnection
    {
        /** @var GetGoogleBusinessConnectionQuery $query */
        return $this->repository->findByUserId($query->userId);
    }
}
