<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services;

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductRenamed;
use Src\Products\Domain\ValueObjects\ProductName;

final class ProductRenameService
{
    public function rename(Product $product, string $name): void
    {
        // Validate name through the Value Object
        $validatedName = ProductName::from($name);

        $product->name = $validatedName;
        $product->recordEvent(new ProductRenamed($product->id, $validatedName->value));
    }
}
