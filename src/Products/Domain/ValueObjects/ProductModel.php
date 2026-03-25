<?php

declare(strict_types=1);

namespace Src\Products\Domain\ValueObjects;

use Src\Shared\Core\Errors\ValidationFailed;

final class ProductModel
{
    public const int MAX_LENGTH = 100;

    private function __construct(
        public readonly string $value,
    ) {}

    public static function from(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw ValidationFailed::because('product_model_empty');
        }

        if (mb_strlen($trimmed) > self::MAX_LENGTH) {
            throw ValidationFailed::because('product_model_too_long', [
                'max' => self::MAX_LENGTH,
                'actual' => mb_strlen($trimmed),
            ]);
        }

        return new self($trimmed);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function value(): string
    {
        return $this->value;
    }
}
