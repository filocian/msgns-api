<?php

declare(strict_types=1);

namespace Src\Products\Application\Resources;

final readonly class ProductListItemResource
{
    /**
     * @param array{id: int, code: string, name: string} $productType
     * @param array{types: array<string, mixed>, size: string|null}|null $business
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $model,
        public bool $active,
        public string $configurationStatus,
        public int $usage,
        public ?string $targetUrl,
        public ?string $assignedAt,
        public array $productType,
        public ?array $business,
    ) {}

    /**
     * @return array{
     *   id: int,
     *   name: string,
     *   model: string,
     *   active: bool,
     *   configuration_status: string,
     *   usage: int,
     *   target_url: string|null,
     *   assigned_at: string|null,
     *   product_type: array{id: int, code: string, name: string},
     *   business: array{types: array<string, mixed>, size: string|null}|null,
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'model' => $this->model,
            'active' => $this->active,
            'configuration_status' => $this->configurationStatus,
            'usage' => $this->usage,
            'target_url' => $this->targetUrl,
            'assigned_at' => $this->assignedAt,
            'product_type' => $this->productType,
            'business' => $this->business,
        ];
    }
}
