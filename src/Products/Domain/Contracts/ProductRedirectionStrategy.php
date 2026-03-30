<?php

declare(strict_types=1);

namespace Src\Products\Domain\Contracts;

use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\ValueObjects\RedirectionContext;
use Src\Products\Domain\ValueObjects\RedirectionTarget;

interface ProductRedirectionStrategy
{
    public function supports(Product $product): bool;

    public function resolve(Product $product, RedirectionContext $context): RedirectionTarget;
}
