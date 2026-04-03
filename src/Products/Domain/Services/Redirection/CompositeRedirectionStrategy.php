<?php

declare(strict_types=1);

namespace Src\Products\Domain\Services\Redirection;

use Src\Products\Domain\Contracts\ProductRedirectionStrategy;
use Src\Products\Domain\Entities\Product;
use Src\Products\Domain\ValueObjects\RedirectionContext;
use Src\Products\Domain\ValueObjects\RedirectionTarget;
use Src\Shared\Core\Errors\NotFound;

final class CompositeRedirectionStrategy implements ProductRedirectionStrategy
{
    /**
     * @param list<ProductRedirectionStrategy> $strategies
     */
    public function __construct(
        private readonly array $strategies,
    ) {}

    public function supports(Product $product): bool
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($product)) {
                return true;
            }
        }

        return false;
    }

    public function resolve(Product $product, RedirectionContext $context): RedirectionTarget
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($product)) {
                return $strategy->resolve($product, $context);
            }
        }

        throw NotFound::entity('redirection-strategy', $product->model->value);
    }
}
