<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\UpdateProductType;

use Src\Shared\Core\Bus\Command;

final readonly class UpdateProductTypeCommand implements Command
{
    public function __construct(
        public int $productTypeId,
        public ?string $code,
        public ?string $name,
        public ?string $primaryModel,
        public ?string $secondaryModel,
    ) {}

    public function commandName(): string
    {
        return 'products.update_product_type';
    }
}
