<?php

declare(strict_types=1);

namespace Src\Products\Application\Resources;

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Entities\ProductType;

final readonly class PublicProductResource
{
    private function __construct(
        public int $id,
        public string $name,
        public string $model,
        public bool $active,
        public string $configurationStatus,
        public ?string $targetUrl,
        public ?string $description,
        public ?string $assignedAt,
        public ?string $size,
        public int $productTypeId,
        public string $productTypeCode,
        public string $productTypePrimaryModel,
    ) {}

    public static function fromEntities(Product $product, ProductType $productType): self
    {
        return new self(
            id: $product->id,
            name: $product->name->value,
            model: $product->model->value,
            active: $product->active,
            configurationStatus: $product->configurationStatus->value,
            targetUrl: $product->targetUrl,
            description: $product->description?->value,
            assignedAt: $product->assignedAt?->format('c'),
            size: $product->size,
            productTypeId: $productType->id,
            productTypeCode: $productType->code->value,
            productTypePrimaryModel: $productType->models->primary,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'model' => $this->model,
            'active' => $this->active,
            'configurationStatus' => $this->configurationStatus,
            'targetUrl' => $this->targetUrl,
            'description' => $this->description,
            'assignedAt' => $this->assignedAt,
            'size' => $this->size,
            'productType' => [
                'id' => $this->productTypeId,
                'code' => $this->productTypeCode,
                'primaryModel' => $this->productTypePrimaryModel,
            ],
        ];
    }
}
