<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Product;
use App\UseCases\Product\Redirect\ProductStatisticsUC;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

final class UpdateProductStatsJob implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

	/**
	 * El número máximo de intentos para este trabajo.
	 *
	 * @var int
	 */
	public int $tries = 6;

	/**
	 * El tiempo de espera antes de reintentar el trabajo.
	 *
	 * @var int|array
	 */
	public array|int $backoff = 10;


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
	public function handle(ProductStatisticsUC $productStatisticsUC): void
	{
		try {
			$productStatisticsUC->run([
				'productModel' => $this->product,
			]);
		} catch (Throwable $e) {
			Log::error('Error UpdateProductStatsJob: ' . $e->getMessage());
			$this->fail($e);
		}
	}
}
