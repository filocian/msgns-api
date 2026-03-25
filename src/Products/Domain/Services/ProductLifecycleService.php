<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services;

use Src\Products\Domain\Ports\ProductRepositoryPort;

final class ProductLifecycleService
{
    public function __construct(
        private readonly ProductRepositoryPort $productRepo,
    ) {}

    public function softDelete(int $productId): void
    {
        $this->productRepo->delete($productId);
    }

    public function restore(int $productId): void
    {
        $this->productRepo->restore($productId);
    }
}
