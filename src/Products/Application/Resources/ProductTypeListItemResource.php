<?php

declare(strict_types=1);

namespace Src\Products\Application\Resources;

final readonly class ProductTypeListItemResource
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public string $primaryModel,
        public ?string $secondaryModel,
        public string $createdAt,
        public string $updatedAt,
    ) {}
}
