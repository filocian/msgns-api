<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product;
use App\UseCases\Product\Redirect\ProductStatisticsUC;
use App\UseCases\Product\Redirect\ProductUsageUC;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class UpdateProductUsageJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	/**
	 * Create a new job instance.
	 */
	public function __construct(public Product $product)
	{
		//
	}

	/**
	 * Execute the job.
	 * @throws Exception
	 */
	public function handle(ProductUsageUC $productUsageUC, ProductStatisticsUC $productStatisticsUC): void
	{
		$productUsageUC->run([
			'productModel' => $this->product,
		]);

		$productStatisticsUC->run([
			'productModel' => $this->product,
		]);
	}
}
