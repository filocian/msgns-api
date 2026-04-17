<?php

declare(strict_types=1);

namespace Src\Billing\Application\Queries\ListStripeProductPrices;

use Src\Billing\Application\Resources\StripePriceResource;
use Src\Billing\Domain\DataTransferObjects\StripeCatalogPrice;
use Src\Billing\Domain\Ports\StripeCatalogPort;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;

final class ListStripeProductPricesHandler implements QueryHandler
{
    public function __construct(
        private readonly StripeCatalogPort $catalog,
    ) {}

    /**
     * @return list<StripePriceResource>
     */
    public function handle(Query $query): array
    {
        assert($query instanceof ListStripeProductPricesQuery);

        $active = array_values(array_filter(
            $this->catalog->listPricesForProduct($query->productId),
            static fn (StripeCatalogPrice $p): bool => $p->active,
        ));

        return array_map(
            static fn (StripeCatalogPrice $p): StripePriceResource => StripePriceResource::fromDomain($p),
            $active,
        );
    }
}
