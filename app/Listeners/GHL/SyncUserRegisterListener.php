<?php

declare(strict_types=1);

namespace App\Listeners\GHL;

use App\Events\User\UserSignedUpEvent;
use App\Jobs\GHL\CreateContactJob;

final class SyncUserRegisterListener
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
		CreateContactJob::dispatch($event);
	}
}
