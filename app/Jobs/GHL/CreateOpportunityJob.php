<?php

declare(strict_types=1);

namespace App\Jobs\GHL;

use App\Events\Product\ProductAssignedEvent;
use App\UseCases\GHL\CreateOpportunityUC;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CreateOpportunityJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	/**
	 * Create a new job instance.
	 */
	public function __construct(public ProductAssignedEvent $event)
	{
		//
	}

	/**
	 * Execute the job.
	 * @throws Exception
	 */
	public function handle(CreateOpportunityUC $createOpportunityUC): void
	{
		$createOpportunityUC->run([
			'product' => $this->event->product,
		]);
	}
}
