<?php

declare(strict_types=1);

namespace Src\Products\Application\Queries\GetPublicProduct;

use Src\Products\Application\Resources\PublicProductResource;
use Src\Products\Domain\Ports\ProductRepositoryPort;
use Src\Products\Domain\Ports\ProductTypeRepository;
use Src\Shared\Core\Bus\Query;
use Src\Shared\Core\Bus\QueryHandler;
use Src\Shared\Core\Errors\NotFound;

final class GetPublicProductHandler implements QueryHandler
{
    public function __construct(
        private readonly ProductRepositoryPort $repository,
        private readonly ProductTypeRepository $typeRepository,
    ) {}

    public function handle(Query $query): PublicProductResource
    {
        assert($query instanceof GetPublicProductQuery);

        $product = $this->repository->findByIdAndPassword($query->productId, $query->password);

        if ($product === null) {
            throw NotFound::entity('product', (string) $query->productId);
        }

        $productType = $this->typeRepository->findById($product->productTypeId);

        if ($productType === null) {
            throw NotFound::entity('product_type', (string) $product->productTypeId);
        }

        return PublicProductResource::fromEntities($product, $productType);
    }
}
