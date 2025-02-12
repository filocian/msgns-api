<?php

declare(strict_types=1);

namespace App\Listeners\Product;

use App\Events\Product\ProductScannedEvent;
use App\Jobs\Product\UpdateProductStatsJob;
use App\Jobs\Product\UpdateProductUsageJob;

final class UpdateProductUsageListener
{
	/**
	 * Create the event listener.
	 */
	public function __construct()
	{
		//
	}

	/**
	 * Handle the event.
	 */
	public function handle(ProductScannedEvent $event): void
	{
		UpdateProductUsageJob::dispatch($event->product);
		UpdateProductStatsJob::dispatch($event->product);
	}
}
