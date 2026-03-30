<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services\Redirection;

use Src\Products\Domain\Contracts\ProductRedirectionStrategy;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Errors\ProductMisconfigured;
use Src\Products\Domain\ValueObjects\RedirectionContext;
use Src\Products\Domain\ValueObjects\RedirectionTarget;
use Src\Products\Domain\ValueObjects\SimpleRedirectionModel;

final class SimpleRedirectionStrategy implements ProductRedirectionStrategy
{
    public function supports(Product $product): bool
    {
        return SimpleRedirectionModel::supports($product->model->value);
    }

    /**
     * Note: $context is intentionally unused for simple redirections.
     * It exists to keep the strategy contract compatible with future flows.
     */
    public function resolve(Product $product, RedirectionContext $context): RedirectionTarget
    {
        if ($product->targetUrl === null) {
            throw ProductMisconfigured::missingTargetUrl($product->id);
        }

        return RedirectionTarget::externalUrl($product->targetUrl);
    }
}
