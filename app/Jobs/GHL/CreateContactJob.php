<?php

declare(strict_types=1);

namespace App\Jobs\GHL;

use App\Events\User\UserSignedUpEvent;
use App\UseCases\GHL\UpdateOrCreateContactUC;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CreateContactJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	/**
	 * Create a new job instance.
	 */
	public function __construct(public UserSignedUpEvent $event)
	{
		//
	}

	/**
	 * Execute the job.
	 * @throws Exception
	 */
	public function handle(UpdateOrCreateContactUC $createGHLContactUC): void
	{
		$createGHLContactUC->run([
			'user' => $this->event->user,
		]);
	}
}
