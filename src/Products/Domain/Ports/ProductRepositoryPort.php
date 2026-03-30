<?php

declare(strict_types=1);

namespace Src\Products\Domain\Ports;

use Src\Products\Domain\Entities\Product;

interface ProductRepositoryPort
{
    public function findById(int $id): ?Product;

    public function findByIdAndPassword(int $id, string $password): ?Product;

    public function findByIdWithTrashed(int $id): ?Product;

    public function save(Product $product): Product;

    public function delete(int $id): void;

    public function restore(int $id): void;

    /**
     * @param array<Product> $products
     */
    public function bulkInsert(array $products): void;

    /**
     * Insert products in chunks and return assigned IDs in insertion order.
     *
     * @param list<Product> $products
     * @param int $chunkSize
     * @return list<int> Assigned database IDs in insertion order
     */
    public function bulkInsertAndReturnIds(array $products, int $chunkSize = 1000): array;

    /**
     * Update product names in batch using a single CASE WHEN query.
     *
     * @param array<int, string> $idToName Map of product ID → new name
     */
    public function bulkUpdateNames(array $idToName): void;
}
