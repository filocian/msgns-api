<?php

declare(strict_types=1);

namespace App\Jobs\MixPanel;

use App\Events\Product\ProductAssignmentErrorEvent;
use App\Infrastructure\Services\MixPanel\MPLogger;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class MixpanelProductAssignmentErrorJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	/**
	 * Create a new job instance.
	 */
	public function __construct(public ProductAssignmentErrorEvent $event)
	{
		//
	}

	/**
	 * Execute the job.
	 * @throws Exception
	 */
	public function handle(MPLogger $mpLogger): void
	{
		$mpLogger->error('PRODUCT_ASSIGNATION', 'ERROR ASSIGNING', 'error assigning a product to a user', [
			'user_id' => $this->event->userId,
			'product_id' => $this->event->productId,
			'exception_message' => $this->event->message,
		]);
	}
}
