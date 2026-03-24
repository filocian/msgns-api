<?php

declare(strict_types=1);

namespace Src\Products\Application\Commands\CreateProductType;

use Src\Shared\Core\Bus\Command;

final readonly class CreateProductTypeCommand implements Command
{
    public function __construct(
        public string $code,
        public string $name,
        public string $primaryModel,
        public ?string $secondaryModel,
    ) {}

    public function commandName(): string
    {
        return 'products.create_product_type';
    }
}
