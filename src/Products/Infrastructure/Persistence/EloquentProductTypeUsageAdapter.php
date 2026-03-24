<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Persistence;

use Illuminate\Support\Facades\DB;
use Src\Products\Domain\Ports\ProductTypeUsagePort;

final class EloquentProductTypeUsageAdapter implements ProductTypeUsagePort
{
    public function isUsed(int $productTypeId): bool
    {
        $inProducts = DB::table('products')
            ->where('product_type_id', $productTypeId)
            ->exists();

        if ($inProducts) {
            return true;
        }

        return DB::table('fancelet_content_gallery')
            ->where('product_type_id', $productTypeId)
            ->exists();
    }
}
