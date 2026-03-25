<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services;

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductsPaired;
use Src\Shared\Core\Errors\ValidationFailed;

final class ProductGroupingService
{
    public function link(Product $primary, Product $secondary): void
    {
        // Validate: same productTypeId
        if ($primary->productTypeId !== $secondary->productTypeId) {
            throw ValidationFailed::because('products_must_have_same_type', [
                'primary_product_type_id' => $primary->productTypeId,
                'secondary_product_type_id' => $secondary->productTypeId,
            ]);
        }

        // Validate: same userId
        if ($primary->userId !== $secondary->userId) {
            throw ValidationFailed::because('products_must_have_same_user');
        }

        // Validate: primary must not already be linked
        if ($primary->linkedToProductId !== null) {
            throw ValidationFailed::because('primary_product_already_linked', [
                'linked_to_product_id' => $primary->linkedToProductId,
            ]);
        }

        // Validate: secondary must not already be linked
        if ($secondary->linkedToProductId !== null) {
            throw ValidationFailed::because('secondary_product_already_linked', [
                'linked_to_product_id' => $secondary->linkedToProductId,
            ]);
        }

        // Link: set primary's linkedToProductId to secondary's id
        $primary->linkedToProductId = $secondary->id;

        // Record event with mainProductId = primary's id, childProductId = secondary's id
        $primary->recordEvent(new ProductsPaired($primary->id, $secondary->id));
    }

    public function unlink(Product $product): void
    {
        $product->linkedToProductId = null;
    }
}
