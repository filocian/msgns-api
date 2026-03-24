<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services;

use Src\Products\Domain\Ports\ProductTypeUsagePort;

final class ProductTypeUsageInspector
{
    public function __construct(
        private readonly ProductTypeUsagePort $usagePort,
    ) {}

    public function isUsed(int $productTypeId): bool
    {
        return $this->usagePort->isUsed($productTypeId);
    }
}
