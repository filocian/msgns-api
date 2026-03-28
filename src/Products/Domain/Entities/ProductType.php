<?php

declare(strict_types=1);

namespace Src\Products\Domain\Entities;

use DateTimeImmutable;
use Src\Shared\Core\Errors\ValidationFailed;
use Src\Products\Domain\ValueObjects\ProductTypeCode;
use Src\Products\Domain\ValueObjects\ProductModels;

final class ProductType
{
    private function __construct(
        public readonly int $id,
        public ProductTypeCode $code,
        public string $name,
        public ?string $description,
        public ProductModels $models,
        public readonly DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {}

    public static function create(
        string $code,
        string $name,
        string $primaryModel,
        ?string $secondaryModel = null,
        ?string $description = null,
    ): self {
        $now = new DateTimeImmutable();

        return new self(
            id: 0,
            code: ProductTypeCode::from($code),
            name: $name,
            description: $description,
            models: ProductModels::from($primaryModel, $secondaryModel),
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public static function fromPersistence(
        int $id,
        string $code,
        string $name,
        string $primaryModel,
        ?string $secondaryModel,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?string $description = null,
    ): self {
        return new self(
            id: $id,
            code: ProductTypeCode::from($code),
            name: $name,
            description: $description,
            models: ProductModels::from($primaryModel, $secondaryModel),
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    /**
     * Apply an update to this product type, enforcing the usage gate.
     *
     * When the product type is in use (isUsed=true), the protected fields
     * `code`, `primary_model`, and `secondary_model` MUST NOT be changed.
     * Attempting to change any protected field while in-use throws ValidationFailed.
     *
     * Non-protected fields (`name`) may always be updated.
     *
     * @throws ValidationFailed when isUsed=true and any protected field differs from the current value
     */
    public function applyUpdate(
        bool $isUsed,
        ?string $code,
        ?string $name,
        ?string $primaryModel,
        ?string $secondaryModel,
    ): void {
        if ($isUsed) {
            $changedProtectedFields = $this->detectProtectedFieldChanges($code, $primaryModel, $secondaryModel);

            if ($changedProtectedFields !== []) {
                throw ValidationFailed::because('protected_fields_immutable', [
                    'fields'  => $changedProtectedFields,
                    'reason'  => 'product_type_in_use',
                ]);
            }
        }

        if ($code !== null) {
            $this->code = ProductTypeCode::from($code);
        }

        if ($name !== null) {
            $this->name = $name;
        }

        if ($primaryModel !== null || $secondaryModel !== null) {
            $newPrimary   = $primaryModel   ?? $this->models->primary;
            $newSecondary = $secondaryModel !== null ? $secondaryModel : $this->models->secondary;
            $this->models = ProductModels::from($newPrimary, $newSecondary);
        }

        $this->updatedAt = new DateTimeImmutable();
    }

    /**
     * Returns the list of protected field names that would change with the given values.
     *
     * @return string[]
     */
    private function detectProtectedFieldChanges(
        ?string $code,
        ?string $primaryModel,
        ?string $secondaryModel,
    ): array {
        $changed = [];

        if ($code !== null && $code !== $this->code->value) {
            $changed[] = 'code';
        }

        if ($primaryModel !== null && $primaryModel !== $this->models->primary) {
            $changed[] = 'primary_model';
        }

        if ($secondaryModel !== null && $secondaryModel !== $this->models->secondary) {
            $changed[] = 'secondary_model';
        }

        return $changed;
    }
}
