<?php

declare(strict_types=1);

namespace Src\Billing\Domain\DataTransferObjects;

final readonly class StripeCatalogProduct
{
    /**
     * @param list<StripeCatalogPrice> $prices
     * @param array<string, string>    $metadata
     */
    public function __construct(
        public string $id,
        public string $name,
        public bool $active,
        public array $prices,
        public array $metadata,
    ) {}
}
