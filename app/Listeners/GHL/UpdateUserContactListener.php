<?php

declare(strict_types=1);

namespace App\Listeners\GHL;

use App\Events\User\UserDataUpdatedEvent;
use App\Jobs\GHL\UpdateContactJob;

final class UpdateUserContactListener
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
	public function handle(UserDataUpdatedEvent $event): void
	{
		UpdateContactJob::dispatch($event);
	}
}
