<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\RemoveProductLink;

use Src\Shared\Core\Bus\Command;

final readonly class RemoveProductLinkCommand implements Command
{
    public function __construct(
        public int $productId,
    ) {}

    public function commandName(): string
    {
        return 'products.remove_product_link';
    }
}
