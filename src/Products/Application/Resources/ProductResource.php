<?php

declare(strict_types=1);

namespace Src\Products\Application\Resources;

use Src\Products\Domain\Entities\Product;

final readonly class ProductResource
{
    public function __construct(
        public int $id,
        public int $productTypeId,
        public ?int $userId,
        public string $model,
        public ?int $linkedToProductId,
        public string $password,
        public ?string $targetUrl,
        public int $usage,
        public string $name,
        public ?string $description,
        public bool $active,
        public string $configurationStatus,
        public ?string $assignedAt,
        public ?string $size,
        public string $createdAt,
        public string $updatedAt,
        public ?string $deletedAt,
    ) {}

    public static function fromEntity(Product $product): self
    {
        return new self(
            id: $product->id,
            productTypeId: $product->productTypeId,
            userId: $product->userId,
            model: $product->model->value,
            linkedToProductId: $product->linkedToProductId,
            password: $product->password->value,
            targetUrl: $product->targetUrl,
            usage: $product->usage,
            name: $product->name->value,
            description: $product->description?->value,
            active: $product->active,
            configurationStatus: $product->configurationStatus->value,
            assignedAt: $product->assignedAt?->format('c'),
            size: $product->size,
            createdAt: $product->createdAt->format('c'),
            updatedAt: $product->updatedAt->format('c'),
            deletedAt: $product->deletedAt?->format('c'),
        );
    }
}
