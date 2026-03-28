<?php

declare(strict_types=1);

use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Shared\Core\Errors\ValidationFailed;

describe('ConfigurationStatus Value Object', function () {

    it('creates from valid status string', function () {
        $vo = ConfigurationStatus::from('not-started');

        expect($vo->value)->toBe('not-started')
            ->and((string) $vo)->toBe('not-started')
            ->and($vo->value())->toBe('not-started');
    });

    it('creates from all valid status strings', function () {
        $statuses = ['not-started', 'assigned', 'target-set', 'business-set', 'completed'];

        foreach ($statuses as $status) {
            $vo = ConfigurationStatus::from($status);
            expect($vo->value)->toBe($status);
        }
    });

    it('creates using static factory method', function () {
        $vo = ConfigurationStatus::notStarted();

        expect($vo->value)->toBe('not-started');
    });

    it('throws on invalid status string', function () {
        ConfigurationStatus::from('invalid-status');
    })->throws(ValidationFailed::class, 'configuration_status_invalid');

    // ─── canTransitionTo() tests ─────────────────────────────────────────────

    it('allows forward transitions', function () {
        $notStarted = ConfigurationStatus::from('not-started');
        $assigned = ConfigurationStatus::from('assigned');
        $targetSet = ConfigurationStatus::from('target-set');
        $businessSet = ConfigurationStatus::from('business-set');
        $completed = ConfigurationStatus::from('completed');

        expect($notStarted->canTransitionTo($assigned))->toBeTrue()
            ->and($notStarted->canTransitionTo($targetSet))->toBeTrue()
            ->and($notStarted->canTransitionTo($businessSet))->toBeTrue()
            ->and($notStarted->canTransitionTo($completed))->toBeTrue()

            ->and($assigned->canTransitionTo($targetSet))->toBeTrue()
            ->and($assigned->canTransitionTo($businessSet))->toBeTrue()
            ->and($assigned->canTransitionTo($completed))->toBeTrue()

            ->and($targetSet->canTransitionTo($businessSet))->toBeTrue()
            ->and($targetSet->canTransitionTo($completed))->toBeTrue()

            ->and($businessSet->canTransitionTo($completed))->toBeTrue();
    });

    it('rejects backward transitions', function () {
        $notStarted = ConfigurationStatus::from('not-started');
        $assigned = ConfigurationStatus::from('assigned');
        $targetSet = ConfigurationStatus::from('target-set');
        $businessSet = ConfigurationStatus::from('business-set');
        $completed = ConfigurationStatus::from('completed');

        expect($assigned->canTransitionTo($notStarted))->toBeFalse()
            ->and($targetSet->canTransitionTo($notStarted))->toBeFalse()
            ->and($businessSet->canTransitionTo($notStarted))->toBeFalse()
            ->and($completed->canTransitionTo($notStarted))->toBeFalse();
    });

    it('rejects same status transitions', function () {
        $notStarted = ConfigurationStatus::from('not-started');
        $assigned = ConfigurationStatus::from('assigned');
        $targetSet = ConfigurationStatus::from('target-set');
        $businessSet = ConfigurationStatus::from('business-set');
        $completed = ConfigurationStatus::from('completed');

        expect($notStarted->canTransitionTo($notStarted))->toBeFalse()
            ->and($assigned->canTransitionTo($assigned))->toBeFalse()
            ->and($targetSet->canTransitionTo($targetSet))->toBeFalse()
            ->and($businessSet->canTransitionTo($businessSet))->toBeFalse()
            ->and($completed->canTransitionTo($completed))->toBeFalse();
    });

    // ─── transitionTo() tests ───────────────────────────────────────────────

    it('returns new status on valid transition', function () {
        $notStarted = ConfigurationStatus::from('not-started');
        $target = ConfigurationStatus::from('assigned');

        $result = $notStarted->transitionTo($target);

        expect($result->value)->toBe('assigned');
    });

    it('throws on invalid transition', function () {
        $assigned = ConfigurationStatus::from('assigned');
        $target = ConfigurationStatus::from('not-started');

        $assigned->transitionTo($target);
    })->throws(ValidationFailed::class, 'configuration_status_invalid_transition');

    // ─── equals() tests ──────────────────────────────────────────────────────

    it('equals returns true for same value', function () {
        $vo1 = ConfigurationStatus::from('assigned');
        $vo2 = ConfigurationStatus::from('assigned');

        expect($vo1->equals($vo2))->toBeTrue();
    });

    it('equals returns false for different value', function () {
        $vo1 = ConfigurationStatus::from('assigned');
        $vo2 = ConfigurationStatus::from('target-set');

        expect($vo1->equals($vo2))->toBeFalse();
    });
});
