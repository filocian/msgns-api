<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\ConfigureProduct;

use Src\Shared\Core\Bus\Command;

final readonly class ConfigureProductCommand implements Command
{
    public function __construct(
        public int $productId,
        public string $targetUrl,
    ) {}

    public function commandName(): string
    {
        return 'products.configure_product';
    }
}
