<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services;

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductReset;
use Src\Products\Domain\Ports\ProductUsagePort;
use Src\Products\Domain\ValueObjects\ConfigurationStatus;
use Src\Products\Domain\ValueObjects\ProductName;

final class ProductResetService
{
    public function __construct(
        private readonly ProductUsagePort $usagePort,
    ) {}

    public function reset(Product $product): void
    {
        // Clear all configurable fields
        $product->userId = null;
        $product->targetUrl = null;
        $product->linkedToProductId = null;
        $product->assignedAt = null;
        $product->usage = 0;
        $product->configurationStatus = ConfigurationStatus::notStarted();

        // Reset name to default format: "{model} ({id})"
        $product->name = ProductName::from(
            sprintf('%s (%d)', $product->model->value, $product->id)
        );

        // Delete usage data
        $this->usagePort->deleteUsage($product->id);

        // Record reset event
        $product->recordEvent(new ProductReset($product->id));
    }
}
