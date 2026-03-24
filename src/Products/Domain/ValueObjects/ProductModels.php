<?php

declare(strict_types=1);

namespace Src\Products\Domain\ValueObjects;

use Src\Shared\Core\Errors\ValidationFailed;

final class ProductModels
{
    private function __construct(
        public readonly string $primary,
        public readonly ?string $secondary,
    ) {}

    public static function from(string $primary, ?string $secondary = null): self
    {
        $trimmedPrimary = trim($primary);

        if ($trimmedPrimary === '') {
            throw ValidationFailed::because('product_primary_model_empty');
        }

        $trimmedSecondary = $secondary !== null ? trim($secondary) : null;

        if ($trimmedSecondary === '') {
            $trimmedSecondary = null;
        }

        return new self($trimmedPrimary, $trimmedSecondary);
    }

    public function primaryEquals(self $other): bool
    {
        return $this->primary === $other->primary;
    }

    public function secondaryEquals(self $other): bool
    {
        return $this->secondary === $other->secondary;
    }

    public function equals(self $other): bool
    {
        return $this->primaryEquals($other) && $this->secondaryEquals($other);
    }
}
