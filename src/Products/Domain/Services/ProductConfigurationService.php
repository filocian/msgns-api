<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services;

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Events\ProductTargetUrlSet;
use Src\Products\Domain\ValueObjects\TargetUrl;

final class ProductConfigurationService
{
    public function setTargetUrl(Product $product, string $url): void
    {
        // Validate URL through the Value Object
        $targetUrl = TargetUrl::from($url);

        $product->targetUrl = $targetUrl->value;
        $product->recordEvent(new ProductTargetUrlSet($product->id, $targetUrl->value));
    }
}
