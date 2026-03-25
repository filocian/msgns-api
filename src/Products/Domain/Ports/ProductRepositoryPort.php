<?php

declare(strict_types=1);

namespace Src\Products\Domain\Ports;

use Src\Products\Domain\Entities\Product;

interface ProductRepositoryPort
{
    public function findById(int $id): ?Product;

    public function findByIdWithTrashed(int $id): ?Product;

    public function save(Product $product): Product;

    public function delete(int $id): void;

    public function restore(int $id): void;

    /**
     * @param array<Product> $products
     */
    public function bulkInsert(array $products): void;
}
