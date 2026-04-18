<?php

declare(strict_types=1);

namespace Src\Instagram\Infrastructure\Adapters;

use App\Models\Product;
use Src\Instagram\Domain\Ports\InstagramProductConfigurationPort;

final class EloquentInstagramProductConfiguration implements InstagramProductConfigurationPort
{
    public function getInstagramAccountIdForProduct(int $productId): ?string
    {
        $product = Product::find($productId);

        if ($product === null) {
            return null;
        }

        /** @var string|null $value */
        $value = $product->getAttribute('instagram_account_id');

        return $value === null ? null : (string) $value;
    }
}
