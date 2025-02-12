<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Infrastructure\Services\MixPanel\MPLogger;
use App\Models\Product;
use App\UseCases\Product\Configuration\ResetUC;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class ResetNonExistentOwnerProducts extends Command
{
	public function __construct(private readonly ResetUC $resetUC, private readonly MPLogger $mpLogger)
	{
		parent::__construct();
	}

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:reset-non-existent-owner-products';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Resets products that owner id is not existing on users table';

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		$affectedProducts = [];

		$products = Product::whereNotNull('user_id')
			->whereDoesntHave('user')
			->get();

		if (count($products ?? []) < 1) {
			$this->mpLogger->info(
				'PRODUCT_OWNER_INVALID_PURGE',
				'NO INVALID OWNERS FOUND',
				'no products with invalid owner found',
				[
					'products_restarted' => $affectedProducts,
				]
			);

			Log::info('COMMAND INVALID OWNER => no products with invalid owner found');

			return;
		}

		foreach ($products as $product) {
			$this->resetUC->run(['id' => $product->id]);
			$affectedProducts[] = $product->id;
		}

		$this->mpLogger->warn('PRODUCT_OWNER_INVALID_PURGE', 'PURGED STUC ON ASSIGNED PRODUCTS', 'products restarted', [
			'products_restarted' => $affectedProducts,
		]);

		Log::alert('COMMAND INVALID OWNER => reset non-existent owner-products');
	}
}
