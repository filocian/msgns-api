<?php

declare(strict_types=1);

namespace Src\Billing\Domain\DataTransferObjects;

final readonly class StripeCatalogPrice
{
    public function __construct(
        public string $id,
        public string $productId,
        public string $currency,
        public int $unitAmount,
        public string $type,
        public ?string $interval,
        public bool $active,
    ) {}
}
