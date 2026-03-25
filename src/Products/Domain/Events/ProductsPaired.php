<?php

declare(strict_types=1);

namespace Src\Products\Domain\Events;

use Src\Shared\Core\Bus\DomainEvent;

final readonly class ProductsPaired implements DomainEvent
{
    public function __construct(
        public int $mainProductId,
        public int $childProductId,
    ) {}

    public function eventName(): string
    {
        return 'products.products_paired';
    }
}
