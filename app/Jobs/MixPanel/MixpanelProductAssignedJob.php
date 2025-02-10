<?php

declare(strict_types=1);

namespace App\Jobs\MixPanel;

use App\Events\Product\ProductAssignedEvent;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\MixPanel\MPLogger;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class MixpanelProductAssignedJob implements ShouldQueue
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
	public function handle(MPLogger $mpLogger): void
	{
		$productDto = ProductDto::fromModel($this->event->product);
		$mpLogger->info('PRODUCT_ASSIGNATION', 'PRODUCT ASSIGNATION OCCURRED', 'product assigned to a user', [
			'user_id' => $productDto->user->id,
			'product_id' => $productDto->id,
		]);
	}
}
