<?php

declare(strict_types=1);

namespace Src\Products\Domain\Events;

use Src\Shared\Core\Bus\DomainEvent;

final readonly class ProductTargetUrlSet implements DomainEvent
{
    public function __construct(
        public int $productId,
        public string $targetUrl,
    ) {}

    public function eventName(): string
    {
        return 'products.product_target_url_set';
    }
}
