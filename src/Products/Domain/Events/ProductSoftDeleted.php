<?php

declare(strict_types=1);

namespace Src\Products\Domain\Events;

use Src\Shared\Core\Bus\DomainEvent;

final readonly class ProductSoftDeleted implements DomainEvent
{
    public function __construct(
        public int $productId,
    ) {}

    public function eventName(): string
    {
        return 'products.product_soft_deleted';
    }
}
