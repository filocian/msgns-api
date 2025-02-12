<?php

declare(strict_types=1);

namespace App\Jobs\GHL;

use App\Events\User\UserDataUpdatedEvent;
use App\UseCases\GHL\UpdateOrCreateContactUC;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class UpdateContactJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	/**
	 * Create a new job instance.
	 */
	public function __construct(public UserDataUpdatedEvent $event)
	{
		//
	}

	/**
	 * Execute the job.
	 * @throws Exception
	 */
	public function handle(UpdateOrCreateContactUC $updateContactUC): void
	{
		$updateContactUC->run([
			'user' => $this->event->user,
		]);
	}
}
