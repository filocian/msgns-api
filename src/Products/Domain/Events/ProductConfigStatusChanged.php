<?php

declare(strict_types=1);

namespace Src\Products\Domain\Events;

use Src\Shared\Core\Bus\DomainEvent;

final readonly class ProductConfigStatusChanged implements DomainEvent
{
    public function __construct(
        public int $productId,
        public string $previousStatus,
        public string $newStatus,
    ) {}

    public function eventName(): string
    {
        return 'products.product_config_status_changed';
    }
}
