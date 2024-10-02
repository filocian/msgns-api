<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ProductScanned;
use App\Jobs\UpdateProductStatsJob;
use App\Jobs\UpdateProductUsageJob;

final class UpdateProductUsage
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
	public function handle(ProductScanned $event): void
	{
		UpdateProductUsageJob::dispatch($event->product);
		UpdateProductStatsJob::dispatch($event->product);
	}
}
