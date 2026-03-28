<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Listeners;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Src\Products\Domain\Events\ProductRenamed;
use Src\Shared\Core\Ports\AnalyticsPort;

final class TrackProductRenamed implements ShouldQueue, ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly AnalyticsPort $analytics,
    ) {}

    public function handle(ProductRenamed $event): void
    {
        try {
            $this->analytics->track('PRODUCT_NAMING', [
                'product_id' => $event->productId,
                'name' => $event->name,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Analytics tracking failed for PRODUCT_NAMING', [
                'product_id' => $event->productId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
