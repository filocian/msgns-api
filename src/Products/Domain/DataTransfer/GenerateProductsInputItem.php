<?php

declare(strict_types=1);

namespace Src\Products\Domain\DataTransfer;

final readonly class GenerateProductsInputItem
{
    public function __construct(
        public int $typeId,
        public int $quantity,
        public ?string $size = null,
        public ?string $description = null,
    ) {}
}
