<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\UserSignedUpEvent;
use App\UseCases\Ghl\CreateGHLContactUC;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CreateGHLContactJob implements ShouldQueue
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
	public function handle(CreateGHLContactUC $createGHLContactUC): void
	{
//		$createGHLContactUC->run([
//			'user' => $this->event->user,
//		]);
	}
}
