<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services;

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

final class ProductConfigStatusService
{
    public function transition(Product $product, string $newStatus): void
    {
        $targetStatus = ConfigurationStatus::from($newStatus);
        $currentStatus = $product->configurationStatus;

        // Check if transition is valid
        if (!$currentStatus->canTransitionTo($targetStatus)) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid configuration status transition from "%s" to "%s"',
                $currentStatus->value,
                $targetStatus->value
            ));
        }

        $product->configurationStatus = $targetStatus;
    }
}
