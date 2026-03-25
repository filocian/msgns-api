<?php

declare(strict_types=1);

namespace Src\Products\Domain\ValueObjects;

use Src\Shared\Core\Errors\ValidationFailed;

final class TargetUrl
{
    private function __construct(
        public readonly string $value,
    ) {}

    public static function from(string $value): self
    {
        $trimmed = trim($value);

        if ($trimmed === '') {
            throw ValidationFailed::because('target_url_empty');
        }

        if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
            throw ValidationFailed::because('target_url_invalid', [
                'provided' => $trimmed,
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
