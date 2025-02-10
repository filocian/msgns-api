<?php

declare(strict_types=1);

namespace App\Jobs\GHL;

use App\Events\Product\ProductConfiguredEvent;
use App\Static\GHL\StaticGHLOpportunities;
use App\UseCases\GHL\UpdateOpportunityUC;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class UpdateOpportunityJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	/**
	 * Create a new job instance.
	 */
	public function __construct(public ProductConfiguredEvent $event)
	{
		//
	}

	/**
	 * Execute the job.
	 * @throws Exception
	 */
	public function handle(UpdateOpportunityUC $updateOpportunityUC): void
	{
		$updateOpportunityUC->run([
			'product' => $this->event->product,
			'stageId' => StaticGHLOpportunities::$PRODUCT_COMPLETED_STAGE_ID,
		]);
	}
}
