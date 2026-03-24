<?php

declare(strict_types=1);

namespace Src\Products\Domain\Ports;

interface ProductTypeUsagePort
{
    /**
     * Returns true if the Product Type with the given id is referenced
     * by any dependent entity (e.g. products, fancelet_content_gallery).
     */
    public function isUsed(int $productTypeId): bool;
}
