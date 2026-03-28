<?php

declare(strict_types=1);

namespace Src\Products\Domain\Events;

use Src\Shared\Core\Bus\DomainEvent;

final readonly class ProductBusinessUpdated implements DomainEvent
{
    /**
     * @param array<string, mixed> $businessData
     */
    public function __construct(
        public int $productId,
        public array $businessData,
    ) {}

    public function eventName(): string
    {
        return 'products.product_business_updated';
    }
}
