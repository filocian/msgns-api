<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\GetProductType;

use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Shared\Core\Errors\NotFound;
use Src\Products\Domain\Ports\ProductTypeRepository;
use Src\Products\Application\Resources\ProductTypeResource;

final class GetProductTypeHandler implements QueryHandler
{
    public function __construct(
        private readonly ProductTypeRepository $repo,
    ) {}

    public function handle(Query $query): ProductTypeResource
    {
        assert($query instanceof GetProductTypeQuery);

        $productType = $this->repo->findById($query->productTypeId);

        if ($productType === null) {
            throw NotFound::entity('product_type', (string) $query->productTypeId);
        }

        return new ProductTypeResource(
            id: $productType->id,
            code: $productType->code->value,
            name: $productType->name,
            primaryModel: $productType->models->primary,
            secondaryModel: $productType->models->secondary,
            createdAt: $productType->createdAt->format('c'),
            updatedAt: $productType->updatedAt->format('c'),
        );
    }
}
