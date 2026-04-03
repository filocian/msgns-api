<?php

declare(strict_types=1);

namespace Src\Products\Domain\Events;

use DateTimeImmutable;
use Src\Shared\Core\Bus\DomainEvent;

final readonly class ProductConfigurationCompleted implements DomainEvent
{
    public function __construct(
        public int $productId,
        public string $model,
        public DateTimeImmutable $completedAt,
    ) {}

    public function eventName(): string
    {
        return 'products.product_configuration_completed';
    }
}
