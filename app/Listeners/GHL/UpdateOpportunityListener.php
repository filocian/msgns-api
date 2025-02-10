<?php

declare(strict_types=1);

namespace App\Listeners\GHL;

use App\Events\Product\ProductConfiguredEvent;
use App\Jobs\GHL\UpdateOpportunityJob;

final class UpdateOpportunityListener
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
	public function handle(ProductConfiguredEvent $event): void
	{
		UpdateOpportunityJob::dispatch($event);
	}
}
