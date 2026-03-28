<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Listeners;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Src\Products\Domain\Events\ProductAssigned;
use Src\Shared\Core\Ports\AnalyticsPort;

final class TrackProductAssigned implements ShouldQueue, ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly AnalyticsPort $analytics,
    ) {}

    public function handle(ProductAssigned $event): void
    {
        try {
            $this->analytics->track('PRODUCT_ASSIGNATION', [
                'user_id' => $event->userId,
                'product_id' => $event->productId,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Analytics tracking failed for PRODUCT_ASSIGNATION', [
                'user_id' => $event->userId,
                'product_id' => $event->productId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
