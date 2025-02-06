<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\ProductScannedEvent;
use App\Events\UserSignedUpEvent;
use App\Jobs\CreateGHLContactJob;
use App\Jobs\UpdateProductStatsJob;
use App\Jobs\UpdateProductUsageJob;

final class SyncUserRegisterWithGHL
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
	public function handle(UserSignedUpEvent $event): void
	{
		CreateGHLContactJob::dispatch($event);
	}
}
