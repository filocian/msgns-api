<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\GenerateProducts;

use Src\Products\Domain\DataTransfer\GenerateProductsInputItem;
use Src\Shared\Core\Bus\Command;

final readonly class GenerateProductsCommand implements Command
{
    /**
     * @param list<GenerateProductsInputItem> $items
     */
    public function __construct(
        public array $items,
        public string $frontUrl,
        public int $passwordLength,
    ) {}

    public function commandName(): string
    {
        return 'products.generate_products';
    }
}
