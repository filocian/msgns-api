<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services;

use DateTimeImmutable;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductAssigned;

final class ProductAssignmentService
{
    public function assign(Product $product, int $userId): void
    {
        $product->userId = $userId;
        $product->assignedAt = new DateTimeImmutable();
        $product->recordEvent(new ProductAssigned($product->id, $userId));
    }
}
