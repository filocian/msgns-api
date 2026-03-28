<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services;

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;

final class ProductCloneService
{
    public function clone(Product $source, Product $target): void
    {
        // Copy targetUrl from source to target
        $target->targetUrl = $source->targetUrl;

        // Copy configurationStatus, but only if target's status can advance to source's status
        $targetStatus = $target->configurationStatus;
        $sourceStatus = $source->configurationStatus;

        if ($targetStatus->canTransitionTo($sourceStatus)) {
            $target->configurationStatus = $sourceStatus;
        }
        // If target can't advance to source status, keep target's current status

        // NOTE: Cloning does NOT record events - it's an infrastructure operation,
        // not a business event
    }
}
