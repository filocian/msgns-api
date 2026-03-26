<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\ActivateProduct;

use Src\Shared\Core\Bus\Command;

final readonly class ActivateProductCommand implements Command
{
    public function __construct(
        public int $productId,
    ) {}

    public function commandName(): string
    {
        return 'products.activate_product';
    }
}
