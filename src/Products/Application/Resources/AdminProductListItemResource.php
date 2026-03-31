<?php

declare(strict_types=1);

namespace Src\Products\Application\Resources;

final readonly class AdminProductListItemResource
{
    /**
     * @param array{id: int, code: string, name: string} $productType
     * @param array{types: array<string, mixed>, size: string|null}|null $business
     * @param array{id: int, name: string, model: string}|null $pairedProduct
     * @param array{id: int, name: string, email: string}|null $user
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
        public ?string $createdAt,
        public array $productType,
        public ?array $business,
        public ?array $pairedProduct,
        public ?array $user,
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
     *   created_at: string|null,
     *   product_type: array{id: int, code: string, name: string},
     *   business: array{types: array<string, mixed>, size: string|null}|null,
     *   paired_product: array{id: int, name: string, model: string}|null,
     *   user: array{id: int, name: string, email: string}|null,
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
            'created_at' => $this->createdAt,
            'product_type' => $this->productType,
            'business' => $this->business,
            'paired_product' => $this->pairedProduct,
            'user' => $this->user,
        ];
    }
}
