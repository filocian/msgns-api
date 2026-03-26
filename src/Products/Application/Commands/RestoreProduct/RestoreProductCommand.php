<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\RestoreProduct;

use Src\Shared\Core\Bus\Command;

final readonly class RestoreProductCommand implements Command
{
    public function __construct(
        public int $productId,
    ) {}

    public function commandName(): string
    {
        return 'products.restore_product';
    }
}
