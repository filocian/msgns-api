<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\RenameProduct;

use Src\Shared\Core\Bus\Command;

final readonly class RenameProductCommand implements Command
{
    public function __construct(
        public int $productId,
        public string $name,
    ) {}

    public function commandName(): string
    {
        return 'products.rename_product';
    }
}
