<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\RegisterProduct;

use Src\Shared\Core\Bus\Command;

final readonly class RegisterProductCommand implements Command
{
    public function __construct(
        public int $productId,
        public int $userId,
        public string $password,
    ) {}

    public function commandName(): string
    {
        return 'products.register_product';
    }
}
