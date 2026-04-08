<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\ConfigureUrlProduct;

use Src\Products\Application\Commands\ConfigureProductCommand;

final readonly class ConfigureUrlProductCommand implements ConfigureProductCommand
{
    public function __construct(
        public int $productId,
        public string $targetUrl,
    ) {}

    public function productId(): int
    {
        return $this->productId;
    }

    public function commandName(): string
    {
        return 'products.configure_product';
    }
}
