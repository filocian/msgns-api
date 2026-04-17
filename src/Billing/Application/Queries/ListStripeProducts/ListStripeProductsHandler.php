<?php

declare(strict_types=1);

namespace Src\Billing\Application\Queries\ListStripeProducts;

use Src\Billing\Domain\Ports\StripeCatalogPort;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;

final class ListStripeProductsHandler implements QueryHandler
{
    public function __construct(
        private readonly StripeCatalogPort $catalog,
    ) {}

    /**
     * @return list<StripeProductResource>
     */
    public function handle(Query $query): array
    {
        assert($query instanceof ListStripeProductsQuery);

        return array_map(
            static fn ($product): StripeProductResource => StripeProductResource::fromDomain($product),
            $this->catalog->listProducts(),
        );
    }
}
