<?php

declare(strict_types=1);

namespace Src\Subscriptions\Application\Queries\ListPublicSubscriptionTypes;

use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Subscriptions\Application\Resources\PublicSubscriptionTypeResource;
use Src\Subscriptions\Domain\Entities\SubscriptionType;
use Src\Subscriptions\Domain\Ports\SubscriptionTypeRepositoryPort;

final class ListPublicSubscriptionTypesHandler implements QueryHandler
{
    public function __construct(
        private readonly SubscriptionTypeRepositoryPort $repo,
    ) {}

    /** @return list<PublicSubscriptionTypeResource> */
    public function handle(Query $query): array
    {
        assert($query instanceof ListPublicSubscriptionTypesQuery);

        $types = $this->repo->listPublicActive();

        return array_map(
            static fn (SubscriptionType $st): PublicSubscriptionTypeResource => PublicSubscriptionTypeResource::fromEntity($st),
            $types,
        );
    }
}
