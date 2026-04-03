<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Listeners;

use Illuminate\Support\Facades\Log;
use Src\Products\Domain\Events\ProductsPaired;
use Src\Products\Infrastructure\Cache\ProductRedirectionCacheService;

final class InvalidateProductRedirectionCache
{
    public function __construct(
        private readonly ProductRedirectionCacheService $cacheService,
    ) {}

    public function handle(object $event): void
    {
        foreach ($this->extractProductIds($event) as $productId) {
            try {
                $this->cacheService->forget($productId);
            } catch (\Throwable $exception) {
                Log::warning('Failed to invalidate product redirection cache', [
                    'product_id' => $productId,
                    'event' => $event::class,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    /**
     * @return list<int>
     */
    private function extractProductIds(object $event): array
    {
        if ($event instanceof ProductsPaired) {
            return [$event->mainProductId, $event->childProductId];
        }

        if (property_exists($event, 'productId') && is_int($event->productId)) {
            return [$event->productId];
        }

        return [];
    }
}
