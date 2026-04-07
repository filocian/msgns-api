<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\UpdateProductDetails;

use Src\Shared\Core\Bus\Command;

final readonly class UpdateProductDetailsCommand implements Command
{
    public function __construct(
        public int $productId,
        public int $actorUserId,
        public ?string $name,
        public ?string $description,
        public bool $hasName,
        public bool $hasDescription,
    ) {}

    public function commandName(): string
    {
        return 'products.update_product_details';
    }
}
