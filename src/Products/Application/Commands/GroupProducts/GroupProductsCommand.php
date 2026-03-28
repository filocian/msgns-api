<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\GroupProducts;

use Src\Shared\Core\Bus\Command;

final readonly class GroupProductsCommand implements Command
{
    public function __construct(
        public int $referenceId,
        public int $candidateId,
    ) {}

    public function commandName(): string
    {
        return 'products.group_products';
    }
}
