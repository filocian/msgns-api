<?php

declare(strict_types=1);

namespace Src\Products\Domain\Ports;

use Src\Shared\Core\Bus\PaginatedResult;
use Src\Products\Domain\Entities\ProductType;

interface ProductTypeRepository
{
    public function findById(int $id): ?ProductType;

    public function save(ProductType $productType): ProductType;

    /**
     * @param array{page?: int, perPage?: int, sortBy?: string, sortDir?: string} $params
     */
    public function list(array $params): PaginatedResult;
}
