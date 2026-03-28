<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Listeners;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Src\Products\Domain\Events\ProductBusinessUpdated;
use Src\Shared\Core\Ports\AnalyticsPort;

final class TrackProductBusinessUpdated implements ShouldQueue, ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly AnalyticsPort $analytics,
    ) {}

    public function handle(ProductBusinessUpdated $event): void
    {
        try {
            $this->analytics->track('PRODUCT_BUSINESS', [
                'product_id' => $event->productId,
                'business_data' => $event->businessData,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Analytics tracking failed for PRODUCT_BUSINESS', [
                'product_id' => $event->productId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
