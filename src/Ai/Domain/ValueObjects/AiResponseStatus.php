<?php

declare(strict_types=1);

namespace Src\Ai\Domain\ValueObjects;

use Src\Shared\Core\Errors\ValidationFailed;

final class AiResponseStatus
{
    public const string PENDING  = 'pending';
    public const string APPROVED = 'approved';
    public const string EDITED   = 'edited';
    public const string REJECTED = 'rejected';
    public const string APPLYING = 'applying';
    public const string APPLIED  = 'applied';
    public const string EXPIRED  = 'expired';

    private const array VALID_STATUSES = [
        self::PENDING,
        self::APPROVED,
        self::EDITED,
        self::REJECTED,
        self::APPLYING,
        self::APPLIED,
        self::EXPIRED,
    ];

    // Non-linear state graph — unlike ConfigurationStatus which is linear (TRANSITION_ORDER)
    // APPLYING is an intermediate state used when publishing is handed off to a queued job
    // (Instagram); synchronous appliers (Google Reviews) skip it and go APPROVED → APPLIED directly.
    // On job failure the worker resets the record to APPROVED via direct write (system rollback
    // bypasses the state machine); APPLYING itself has only one forward transition: APPLIED.
    private const array ALLOWED_TRANSITIONS = [
        self::PENDING  => [self::APPROVED, self::EDITED, self::REJECTED],
        self::APPROVED => [self::APPLIED, self::APPLYING, self::EXPIRED],
        self::EDITED   => [self::APPROVED, self::REJECTED],
        self::APPLYING => [self::APPLIED],
        self::REJECTED => [],  // terminal
        self::APPLIED  => [],  // terminal
        self::EXPIRED  => [],  // terminal
    ];

    private function __construct(
        public readonly string $value,
    ) {}

    public static function from(string $value): self
    {
        $trimmed = trim($value);

        if (! in_array($trimmed, self::VALID_STATUSES, true)) {
            throw ValidationFailed::because('ai_response_status_invalid', [
                'provided' => $trimmed,
                'valid'    => self::VALID_STATUSES,
            ]);
        }

        return new self($trimmed);
    }

    public static function pending(): self
    {
        return new self(self::PENDING);
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target->value, self::ALLOWED_TRANSITIONS[$this->value] ?? [], true);
    }

    public function transitionTo(self $target): self
    {
        if (! $this->canTransitionTo($target)) {
            throw ValidationFailed::because('ai_response_status_invalid_transition', [
                'current' => $this->value,
                'target'  => $target->value,
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
