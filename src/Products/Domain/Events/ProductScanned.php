<?php

declare(strict_types=1);

namespace Src\Products\Domain\Events;

use DateTimeImmutable;
use Src\Shared\Core\Bus\DomainEvent;

final readonly class ProductScanned implements DomainEvent
{
    public function __construct(
        public int $productId,
        public ?int $userId,
        public string $productName,
        public DateTimeImmutable $scannedAt,
    ) {}

    public function eventName(): string
    {
        return 'products.product_scanned';
    }
}
