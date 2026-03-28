<?php

declare(strict_types=1);

namespace Src\Products\Domain\ValueObjects;

use Src\Shared\Core\Errors\ValidationFailed;

final class ConfigurationStatus
{
    public const string NOT_STARTED = 'not-started';
    public const string ASSIGNED = 'assigned';
    public const string TARGET_SET = 'target-set';
    public const string BUSINESS_SET = 'business-set';
    public const string COMPLETED = 'completed';

    private const array VALID_STATUSES = [
        self::NOT_STARTED,
        self::ASSIGNED,
        self::TARGET_SET,
        self::BUSINESS_SET,
        self::COMPLETED,
    ];

    private const array TRANSITION_ORDER = [
        self::NOT_STARTED => 0,
        self::ASSIGNED => 1,
        self::TARGET_SET => 2,
        self::BUSINESS_SET => 3,
        self::COMPLETED => 4,
    ];

    private function __construct(
        public readonly string $value,
    ) {}

    public static function from(string $value): self
    {
        $trimmed = trim($value);

        if (!in_array($trimmed, self::VALID_STATUSES, true)) {
            throw ValidationFailed::because('configuration_status_invalid', [
                'provided' => $trimmed,
                'valid' => self::VALID_STATUSES,
            ]);
        }

        return new self($trimmed);
    }

    public static function notStarted(): self
    {
        return new self(self::NOT_STARTED);
    }

    public function canTransitionTo(self $target): bool
    {
        $currentOrder = self::TRANSITION_ORDER[$this->value];
        $targetOrder = self::TRANSITION_ORDER[$target->value];

        // Can only transition forward (target > current)
        return $targetOrder > $currentOrder;
    }

    public function transitionTo(self $target): self
    {
        if (!$this->canTransitionTo($target)) {
            throw ValidationFailed::because('configuration_status_invalid_transition', [
                'current' => $this->value,
                'target' => $target->value,
            ]);
        }

        return $target;
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
