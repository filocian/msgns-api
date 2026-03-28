<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Listeners;

use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Src\Products\Domain\Events\ProductsPaired;
use Src\Shared\Core\Ports\AnalyticsPort;

final class TrackProductsPaired implements ShouldQueue, ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly AnalyticsPort $analytics,
    ) {}

    public function handle(ProductsPaired $event): void
    {
        try {
            $this->analytics->track('PRODUCT_PAIRING', [
                'main_product' => $event->mainProductId,
                'child_product' => $event->childProductId,
            ]);
        } catch (\Throwable $exception) {
            Log::error('Analytics tracking failed for PRODUCT_PAIRING', [
                'main_product' => $event->mainProductId,
                'child_product' => $event->childProductId,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
