<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Listeners;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Src\Products\Domain\Events\ProductTargetUrlSet;
use Src\Shared\Core\Ports\AnalyticsPort;

final class TrackProductTargetUrlSet implements ShouldQueue, ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly AnalyticsPort $analytics,
    ) {}

    public function handle(ProductTargetUrlSet $event): void
    {
        try {
            $this->analytics->track('PRODUCT_CONFIGURATION', [
                'product_id' => $event->productId,
                'target_url' => $event->targetUrl,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Analytics tracking failed for PRODUCT_CONFIGURATION', [
                'product_id' => $event->productId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
