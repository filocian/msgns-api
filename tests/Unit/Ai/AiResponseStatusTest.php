<?php

declare(strict_types=1);

use Src\Ai\Domain\ValueObjects\AiResponseStatus;
use Src\Shared\Core\Errors\ValidationFailed;

describe('AiResponseStatus', function (): void {

    // ─── from() ───────────────────────────────────────────────────────────────

    it('creates a valid status from string', function (): void {
        $status = AiResponseStatus::from('pending');
        expect($status->value)->toBe('pending');
    });

    it('trims whitespace from input', function (): void {
        $status = AiResponseStatus::from('  approved  ');
        expect($status->value)->toBe('approved');
    });

    it('throws ValidationFailed for invalid status string', function (): void {
        expect(fn () => AiResponseStatus::from('invalid'))->toThrow(ValidationFailed::class);
    });

    it('throws ValidationFailed not DomainException for invalid status', function (): void {
        try {
            AiResponseStatus::from('unknown');
            $this->fail('Expected exception');
        } catch (ValidationFailed $e) {
            expect($e)->toBeInstanceOf(ValidationFailed::class);
        }
    });

    // ─── pending() factory ────────────────────────────────────────────────────

    it('creates pending status via factory method', function (): void {
        expect(AiResponseStatus::pending()->value)->toBe('pending');
    });

    // ─── Allowed transitions ──────────────────────────────────────────────────

    it('allows pending → approved', function (): void {
        $status = AiResponseStatus::from('pending');
        $result = $status->transitionTo(AiResponseStatus::from('approved'));
        expect($result->value)->toBe('approved');
    });

    it('allows pending → edited', function (): void {
        $status = AiResponseStatus::from('pending');
        $result = $status->transitionTo(AiResponseStatus::from('edited'));
        expect($result->value)->toBe('edited');
    });

    it('allows pending → rejected', function (): void {
        $status = AiResponseStatus::from('pending');
        $result = $status->transitionTo(AiResponseStatus::from('rejected'));
        expect($result->value)->toBe('rejected');
    });

    it('allows edited → approved', function (): void {
        $status = AiResponseStatus::from('edited');
        $result = $status->transitionTo(AiResponseStatus::from('approved'));
        expect($result->value)->toBe('approved');
    });

    it('allows edited → rejected', function (): void {
        $status = AiResponseStatus::from('edited');
        $result = $status->transitionTo(AiResponseStatus::from('rejected'));
        expect($result->value)->toBe('rejected');
    });

    it('allows approved → applied', function (): void {
        $status = AiResponseStatus::from('approved');
        $result = $status->transitionTo(AiResponseStatus::from('applied'));
        expect($result->value)->toBe('applied');
    });

    it('allows approved → expired', function (): void {
        $status = AiResponseStatus::from('approved');
        $result = $status->transitionTo(AiResponseStatus::from('expired'));
        expect($result->value)->toBe('expired');
    });

    // ─── Disallowed transitions ───────────────────────────────────────────────

    it('throws ValidationFailed for pending → applied (disallowed)', function (): void {
        $status = AiResponseStatus::from('pending');
        expect(fn () => $status->transitionTo(AiResponseStatus::from('applied')))
            ->toThrow(ValidationFailed::class);
    });

    it('throws ValidationFailed for pending → expired (disallowed)', function (): void {
        $status = AiResponseStatus::from('pending');
        expect(fn () => $status->transitionTo(AiResponseStatus::from('expired')))
            ->toThrow(ValidationFailed::class);
    });

    it('throws ValidationFailed for rejected → approved (terminal state)', function (): void {
        $status = AiResponseStatus::from('rejected');
        expect(fn () => $status->transitionTo(AiResponseStatus::from('approved')))
            ->toThrow(ValidationFailed::class);
    });

    it('throws ValidationFailed for applied → anything (terminal state)', function (): void {
        $status = AiResponseStatus::from('applied');
        expect(fn () => $status->transitionTo(AiResponseStatus::from('pending')))
            ->toThrow(ValidationFailed::class);
    });

    it('throws ValidationFailed for expired → anything (terminal state)', function (): void {
        $status = AiResponseStatus::from('expired');
        expect(fn () => $status->transitionTo(AiResponseStatus::from('pending')))
            ->toThrow(ValidationFailed::class);
    });

    // ─── Terminal states have zero valid transitions ───────────────────────────

    it('rejected has zero valid transitions', function (): void {
        $status = AiResponseStatus::from('rejected');
        $targets = ['pending', 'approved', 'edited', 'applied', 'expired'];
        foreach ($targets as $target) {
            expect($status->canTransitionTo(AiResponseStatus::from($target)))->toBeFalse();
        }
    });

    it('applied has zero valid transitions', function (): void {
        $status = AiResponseStatus::from('applied');
        $targets = ['pending', 'approved', 'edited', 'rejected', 'expired'];
        foreach ($targets as $target) {
            expect($status->canTransitionTo(AiResponseStatus::from($target)))->toBeFalse();
        }
    });

    it('expired has zero valid transitions', function (): void {
        $status = AiResponseStatus::from('expired');
        $targets = ['pending', 'approved', 'edited', 'rejected', 'applied'];
        foreach ($targets as $target) {
            expect($status->canTransitionTo(AiResponseStatus::from($target)))->toBeFalse();
        }
    });

    // ─── canTransitionTo() ────────────────────────────────────────────────────

    it('canTransitionTo returns true for allowed transitions', function (): void {
        expect(AiResponseStatus::from('pending')->canTransitionTo(AiResponseStatus::from('approved')))->toBeTrue();
        expect(AiResponseStatus::from('edited')->canTransitionTo(AiResponseStatus::from('approved')))->toBeTrue();
        expect(AiResponseStatus::from('approved')->canTransitionTo(AiResponseStatus::from('applied')))->toBeTrue();
    });

    it('canTransitionTo returns false for disallowed transitions', function (): void {
        expect(AiResponseStatus::from('pending')->canTransitionTo(AiResponseStatus::from('applied')))->toBeFalse();
        expect(AiResponseStatus::from('rejected')->canTransitionTo(AiResponseStatus::from('pending')))->toBeFalse();
    });

    // ─── equals() ─────────────────────────────────────────────────────────────

    it('equals returns true for same status', function (): void {
        expect(AiResponseStatus::from('pending')->equals(AiResponseStatus::from('pending')))->toBeTrue();
    });

    it('equals returns false for different statuses', function (): void {
        expect(AiResponseStatus::from('pending')->equals(AiResponseStatus::from('approved')))->toBeFalse();
    });

    // ─── value() and __toString() ─────────────────────────────────────────────

    it('value() returns the raw string', function (): void {
        expect(AiResponseStatus::from('approved')->value())->toBe('approved');
    });

    it('__toString() returns the raw string', function (): void {
        expect((string) AiResponseStatus::from('rejected'))->toBe('rejected');
    });
});
