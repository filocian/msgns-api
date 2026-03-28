<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services;

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductActivated;
use Src\Products\Domain\Events\ProductDeactivated;

final class ProductActivationService
{
    public function activate(Product $product): void
    {
        // Only record event if changing from inactive to active
        if (!$product->active) {
            $product->active = true;
            $product->recordEvent(new ProductActivated($product->id));
        }
    }

    public function deactivate(Product $product): void
    {
        // Only record event if changing from active to inactive
        if ($product->active) {
            $product->active = false;
            $product->recordEvent(new ProductDeactivated($product->id));
        }
    }
}
