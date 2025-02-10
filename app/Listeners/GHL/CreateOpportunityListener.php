<?php

declare(strict_types=1);

namespace App\Listeners\GHL;

use App\Events\Product\ProductAssignedEvent;
use App\Jobs\GHL\CreateOpportunityJob;

final class CreateOpportunityListener
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
	public function handle(ProductAssignedEvent $event): void
	{
		CreateOpportunityJob::dispatch($event);
	}
}
