<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\ResetProduct;

use Src\Shared\Core\Bus\Command;

final readonly class ResetProductCommand implements Command
{
    public function __construct(
        public int $productId,
    ) {}

    public function commandName(): string
    {
        return 'products.reset_product';
    }
}
