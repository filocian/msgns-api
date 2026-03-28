<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Listeners;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Src\Products\Domain\Events\ProductDeactivated;
use Src\Shared\Core\Ports\AnalyticsPort;

final class TrackProductDeactivated implements ShouldQueue, ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly AnalyticsPort $analytics,
    ) {}

    public function handle(ProductDeactivated $event): void
    {
        try {
            $this->analytics->track('PRODUCT_DISABLED', [
                'product_id' => $event->productId,
                'active' => false,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Analytics tracking failed for PRODUCT_DISABLED', [
                'product_id' => $event->productId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
