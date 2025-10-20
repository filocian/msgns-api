<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Infrastructure\Services\MixPanel\MPLogger;
use App\Models\Product;
use App\UseCases\Product\Configuration\ResetUC;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

final class ResetStuckOnAssignedProductsCommand extends Command
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
	protected $signature = 'app:reset-stuck-on-assigned-products-command';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Resets products that are stuck on assigned status after configured time ';

	/**
	 *  Time for deadline in days: time to subtract from "now"
	 *
	 * @var int
	 */
	private int $time = 2;

	/**
	 * Execute the console command.
	 */
	public function handle(): void
	{
		$deadline = Carbon::now()->subDays($this->time);
		$affectedProducts = [];

		$products = Product::where('configuration_status', 'assigned')
			->where('assigned_at', '<=', $deadline)
			->get();

		$productsCount = count($products);

		if ($productsCount ?? [] < 1) {
			$this->mpLogger->info('ASSIGNED_PURGE', 'NO STUCK ON ASSIGNED PRODUCTS', 'no products stuck found', [
				'products_restarted' => $affectedProducts,
			]);

			Log::info('COMMAND STUCK ON ASSIGNED => no products stuck found');
			$this->line('Deadline Date Applied: ' .$deadline. '.');
			$this->line('  ✅ No products with status assigned, and more than ' .$this->time. ' days found.');

			return;
		}

		foreach ($products as $product) {
			$this->resetUC->run(['id' => $product->id]);
			$affectedProducts[] = $product->id;
		}

		$this->mpLogger->warn('ASSIGNED_PURGE', 'PURGED STUCK ON ASSIGNED PRODUCTS', 'products restarted', [
			'products_restarted' => $affectedProducts,
		]);

		Log::alert('COMMAND STUCK ON ASSIGNED => ' . $productsCount . ' Products have been restored to "not-started" status: ' . implode(',', $affectedProducts));
		$this->line('Deadline Date Applied: ' .$deadline. '.');
		$this->line('  ⚠️' . $productsCount . 'Products have been restored to "not-started" status: ' . implode(',', $affectedProducts));
	}
}
