<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\SoftRemoveProduct;

use Src\Shared\Core\Bus\Command;

final readonly class SoftRemoveProductCommand implements Command
{
    public function __construct(
        public int $productId,
    ) {}

    public function commandName(): string
    {
        return 'products.soft_remove_product';
    }
}
