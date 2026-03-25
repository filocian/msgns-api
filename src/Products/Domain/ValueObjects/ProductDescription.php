<?php

declare(strict_types=1);

namespace Src\Products\Domain\ValueObjects;

final class ProductDescription
{
    private function __construct(
        public readonly ?string $value,
    ) {}

    public static function from(?string $value): self
    {
        return new self($value !== null ? trim($value) : null);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value ?? '';
    }

    public function value(): ?string
    {
        return $this->value;
    }
}
