<?php

declare(strict_types=1);

use Src\Products\Domain\Errors\UnsupportedProductModel;
use Src\Products\Domain\Services\ConfigurationFlowResolver;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

describe('ConfigurationFlowResolver', function () {
    it('returns applicable states for every supported simple model', function (string $model) {
        $resolver = new ConfigurationFlowResolver();
        $states = array_map(static fn (ConfigurationStatus $status): string => $status->value, $resolver->applicableStates($model));

        expect($states)->toBe([
            ConfigurationStatus::NOT_STARTED,
            ConfigurationStatus::ASSIGNED,
            ConfigurationStatus::TARGET_SET,
            ConfigurationStatus::COMPLETED,
        ]);
    })->with(['google', 'instagram', 'youtube', 'tiktok', 'facebook', 'info']);

    it('resolves next states and skip validation', function () {
        $resolver = new ConfigurationFlowResolver();

        expect($resolver->nextState('google', ConfigurationStatus::from(ConfigurationStatus::TARGET_SET))?->value)
            ->toBe(ConfigurationStatus::COMPLETED)
            ->and($resolver->nextState('google', ConfigurationStatus::from(ConfigurationStatus::ASSIGNED))?->value)->toBe(ConfigurationStatus::TARGET_SET)
            ->and($resolver->nextState('google', ConfigurationStatus::from(ConfigurationStatus::COMPLETED)))->toBeNull()
            ->and($resolver->canSkipTo('google', ConfigurationStatus::from(ConfigurationStatus::TARGET_SET), ConfigurationStatus::from(ConfigurationStatus::COMPLETED)))->toBeTrue()
            ->and($resolver->canSkipTo('google', ConfigurationStatus::from(ConfigurationStatus::BUSINESS_SET), ConfigurationStatus::from(ConfigurationStatus::COMPLETED)))->toBeTrue()
            ->and($resolver->canSkipTo('google', ConfigurationStatus::from(ConfigurationStatus::ASSIGNED), ConfigurationStatus::from(ConfigurationStatus::COMPLETED)))->toBeFalse()
            ->and($resolver->canSkipTo('google', ConfigurationStatus::from(ConfigurationStatus::NOT_STARTED), ConfigurationStatus::from(ConfigurationStatus::COMPLETED)))->toBeFalse();
    });

    it('throws for unsupported models', function () {
        $resolver = new ConfigurationFlowResolver();

        expect(fn () => $resolver->applicableStates('whatsapp'))->toThrow(UnsupportedProductModel::class);
    });
});
