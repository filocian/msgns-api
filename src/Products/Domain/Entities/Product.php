<?php

declare(strict_types=1);

namespace Src\Products\Domain\Entities;

use DateTimeImmutable;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Products\Domain\ValueObjects\ProductDescription;
use Src\Products\Domain\ValueObjects\ProductModel;
use Src\Products\Domain\ValueObjects\ProductName;
use Src\Products\Domain\ValueObjects\ProductPassword;
use Src\Products\Domain\ValueObjects\TargetUrl;

final class Product
{
    /**
     * @var list<object>
     */
    private array $events = [];

    private function __construct(
        public readonly int $id,
        public int $productTypeId,
        public ?int $userId,
        public ProductModel $model,
        public ?int $linkedToProductId,
        public ProductPassword $password,
        public ?string $targetUrl,
        public int $usage,
        public ProductName $name,
        public ?ProductDescription $description,
        public bool $active,
        public ConfigurationStatus $configurationStatus,
        public ?DateTimeImmutable $assignedAt,
        public ?string $size,
        public readonly DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $deletedAt,
    ) {}

    public static function create(
        int $productTypeId,
        string $model,
        string $password,
    ): self {
        $now = new DateTimeImmutable();
        $productModel = ProductModel::from($model);

        return new self(
            id: 0,
            productTypeId: $productTypeId,
            userId: null,
            model: $productModel,
            linkedToProductId: null,
            password: ProductPassword::from($password),
            targetUrl: null,
            usage: 0,
            name: ProductName::from($productModel->value), // Will be updated with ID after persist
            description: null,
            active: false,
            configurationStatus: ConfigurationStatus::notStarted(),
            assignedAt: null,
            size: null,
            createdAt: $now,
            updatedAt: $now,
            deletedAt: null,
        );
    }

    public static function fromPersistence(
        int $id,
        int $productTypeId,
        ?int $userId,
        string $model,
        ?int $linkedToProductId,
        string $password,
        ?string $targetUrl,
        int $usage,
        string $name,
        ?string $description,
        bool $active,
        ConfigurationStatus $configurationStatus,
        ?DateTimeImmutable $assignedAt,
        ?string $size,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?DateTimeImmutable $deletedAt,
    ): self {
        return new self(
            id: $id,
            productTypeId: $productTypeId,
            userId: $userId,
            model: ProductModel::from($model),
            linkedToProductId: $linkedToProductId,
            password: ProductPassword::from($password),
            targetUrl: $targetUrl,
            usage: $usage,
            name: ProductName::from($name),
            description: ProductDescription::from($description),
            active: $active,
            configurationStatus: $configurationStatus,
            assignedAt: $assignedAt,
            size: $size,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
            deletedAt: $deletedAt,
        );
    }

    /**
     * Return a copy of this product with a DB-assigned ID.
     *
     * Called post-bulkInsert to assign the auto-increment ID before name generation.
     * All other properties are preserved exactly.
     */
    public function withAssignedId(int $id): self
    {
        return new self(
            id: $id,
            productTypeId: $this->productTypeId,
            userId: $this->userId,
            model: $this->model,
            linkedToProductId: $this->linkedToProductId,
            password: $this->password,
            targetUrl: $this->targetUrl,
            usage: $this->usage,
            name: $this->name,
            description: $this->description,
            active: $this->active,
            configurationStatus: $this->configurationStatus,
            assignedAt: $this->assignedAt,
            size: $this->size,
            createdAt: $this->createdAt,
            updatedAt: $this->updatedAt,
            deletedAt: $this->deletedAt,
        );
    }

    /**
     * Generate the default name: "{model} ({id})"
     */
    public function generateDefaultName(): void
    {
        $this->name = ProductName::from(sprintf('%s (%d)', $this->model->value, $this->id));
    }

    /**
     * @param object $event
     */
    public function recordEvent(object $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return list<object>
     */
    public function releaseEvents(): array
    {
        $released = $this->events;
        $this->events = [];

        return $released;
    }

    /**
     * Check if product has recorded events
     */
    public function hasEvents(): bool
    {
        return $this->events !== [];
    }
}
