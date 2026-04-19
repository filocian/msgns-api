<?php

declare(strict_types=1);

namespace Src\Instagram\Domain\Ports;

interface InstagramProductConfigurationPort
{
    /**
     * Look up the `instagram_account_id` configured on the given product.
     *
     * Returns null when:
     *   - the product does not exist, OR
     *   - the product exists but `instagram_account_id` is null.
     *
     * Callers distinguish between "missing product" and "missing config" by
     * checking upstream ownership (handler) — this port does NOT throw.
     */
    public function getInstagramAccountIdForProduct(int $productId): ?string;
}
