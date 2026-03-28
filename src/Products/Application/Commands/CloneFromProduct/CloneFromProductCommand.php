<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\CloneFromProduct;

use Src\Shared\Core\Bus\Command;

final readonly class CloneFromProductCommand implements Command
{
    public function __construct(
        public int $targetId,
        public int $sourceId,
    ) {}

    public function commandName(): string
    {
        return 'products.clone_from_product';
    }
}
