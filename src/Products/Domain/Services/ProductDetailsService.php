<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services;

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\ValueObjects\ProductDescription;
use Src\Products\Domain\ValueObjects\ProductName;
use Src\Shared\Core\Errors\ValidationFailed;

final class ProductDetailsService
{
    public function apply(
        Product $product,
        bool $hasName,
        ?string $name,
        bool $hasDescription,
        ?string $description,
    ): void {
        if (!$hasName && !$hasDescription) {
            throw ValidationFailed::because('product_details_empty_payload');
        }

        if ($hasName) {
            $product->name = ProductName::from((string) $name);
        }

        if ($hasDescription) {
            $product->description = ProductDescription::from($description);
        }
    }
}
