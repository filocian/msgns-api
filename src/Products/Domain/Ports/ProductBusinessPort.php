<?php

declare(strict_types=1);

namespace Src\Products\Domain\Ports;

use Src\Products\Domain\Entities\ProductBusiness;

interface ProductBusinessPort
{
    public function findByProductId(int $productId): ?ProductBusiness;

    public function save(ProductBusiness $business): ProductBusiness;
}
