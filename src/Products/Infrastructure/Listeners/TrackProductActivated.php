<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Listeners;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Src\Products\Domain\Events\ProductActivated;
use Src\Shared\Core\Ports\AnalyticsPort;

final class TrackProductActivated implements ShouldQueue, ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly AnalyticsPort $analytics,
    ) {}

    public function handle(ProductActivated $event): void
    {
        try {
            $this->analytics->track('PRODUCT_ENABLED', [
                'product_id' => $event->productId,
                'active' => true,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Analytics tracking failed for PRODUCT_ENABLED', [
                'product_id' => $event->productId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
