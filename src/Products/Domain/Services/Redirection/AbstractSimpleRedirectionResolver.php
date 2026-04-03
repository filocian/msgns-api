<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services\Redirection;

use Src\Products\Domain\Contracts\ProductRedirectionStrategy;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\Errors\ProductMisconfigured;
use Src\Products\Domain\ValueObjects\RedirectionContext;
use Src\Products\Domain\ValueObjects\RedirectionTarget;
use Src\Products\Domain\ValueObjects\SimpleRedirectionModel;

abstract class AbstractSimpleRedirectionResolver implements ProductRedirectionStrategy
{
    abstract protected function supportedModel(): SimpleRedirectionModel;

    public function supports(Product $product): bool
    {
        return $product->model->value === $this->supportedModel()->value;
    }

    public function resolve(Product $product, RedirectionContext $context): RedirectionTarget
    {
        unset($context);

        if ($product->targetUrl === null) {
            throw ProductMisconfigured::missingTargetUrl($product->id);
        }

        return RedirectionTarget::externalUrl($product->targetUrl);
    }
}
