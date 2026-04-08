<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services;

use Src\Products\Domain\Errors\UnsupportedProductModel;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

final class ConfigurationFlowResolver
{
    private const array FULL_FLOW = [
        ConfigurationStatus::NOT_STARTED,
        ConfigurationStatus::ASSIGNED,
        ConfigurationStatus::TARGET_SET,
        ConfigurationStatus::BUSINESS_SET,
        ConfigurationStatus::COMPLETED,
    ];

    /**
     * @var array<string, list<string>>
     */
    private const array SKIPPABLE_STATES = [
        'google' => [ConfigurationStatus::BUSINESS_SET],
        'instagram' => [ConfigurationStatus::BUSINESS_SET],
        'youtube' => [ConfigurationStatus::BUSINESS_SET],
        'tiktok' => [ConfigurationStatus::BUSINESS_SET],
        'facebook' => [ConfigurationStatus::BUSINESS_SET],
        'info' => [ConfigurationStatus::BUSINESS_SET],
        'whatsapp' => [ConfigurationStatus::BUSINESS_SET],
    ];

    /**
     * @return list<ConfigurationStatus>
     */
    public function applicableStates(string $model): array
    {
        $registeredSkips = self::SKIPPABLE_STATES[$model] ?? null;

        if ($registeredSkips === null) {
            throw UnsupportedProductModel::forModel($model);
        }

        $states = array_values(array_filter(
            self::FULL_FLOW,
            static fn (string $status): bool => !in_array($status, $registeredSkips, true),
        ));

        return array_map(
            static fn (string $status): ConfigurationStatus => ConfigurationStatus::from($status),
            $states,
        );
    }

    public function nextState(string $model, ConfigurationStatus $current): ?ConfigurationStatus
    {
        $states = $this->applicableStates($model);

        foreach ($states as $index => $state) {
            if ($state->equals($current)) {
                return $states[$index + 1] ?? null;
            }
        }

        return null;
    }

    public function canSkipTo(string $model, ConfigurationStatus $current, ConfigurationStatus $target): bool
    {
        $registeredSkips = self::SKIPPABLE_STATES[$model] ?? null;

        if ($registeredSkips === null) {
            throw UnsupportedProductModel::forModel($model);
        }

        $currentIndex = array_search($current->value, self::FULL_FLOW, true);
        $targetIndex = array_search($target->value, self::FULL_FLOW, true);

        if (!is_int($currentIndex) || !is_int($targetIndex) || $targetIndex <= $currentIndex) {
            return false;
        }

        for ($index = $currentIndex + 1; $index < $targetIndex; $index++) {
            if (!in_array(self::FULL_FLOW[$index], $registeredSkips, true)) {
                return false;
            }
        }

        return true;
    }
}
