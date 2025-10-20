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
		$deadlineString = $deadline->toDateTimeString();
		$affectedProducts = [];

		$this->info("🚀 Searching 'assigned' products older than: {$deadlineString}");

		$products = Product::where('configuration_status', 'assigned')
			->where('assigned_at', '<=', $deadline)
			->get();

		if ($products->isEmpty()) {
			$this->mpLogger->info('ASSIGNED_PURGE', 'NO STUCK ON ASSIGNED PRODUCTS', 'no products stuck found', [
				'products_restarted' => $affectedProducts,
			]);

			Log::info('COMMAND STUCK ON ASSIGNED => no products stuck found');
			$this->line('Deadline Date Applied: ' . $deadlineString . '.');
			$this->info('  ✅ No products with status assigned, and more than ' . $this->time . ' days found.');

			return;
		}

		$productsCount = $products->count();
		$this->warn("⚠️ {$productsCount} stuck products founds. starting reset...");

		foreach ($products as $product) {
			$this->line("  -> Processing product ID: {$product->id}");

			try {
				$this->resetUC->run(['id' => (int) $product->id]);
				$affectedProducts[] = (int) $product->id;
				$this->comment("    -> ID: {$product->id} reset successful.");
			} catch (\Exception $e) {
				$this->error("    -> error resetting product ID: {$product->id}. Error: {$e->getMessage()}");
				Log::error("Failed to reset product ID {$product->id}: {$e->getMessage()}");
			}
		}

		$productsRestoredCount = count($affectedProducts);
		$productsRestoredList = implode(', ', $affectedProducts);

		$this->mpLogger->warn('ASSIGNED_PURGE', 'PURGED STUCK ON ASSIGNED PRODUCTS', 'products restarted', [
			'products_restarted' => $affectedProducts,
		]);

		Log::alert('COMMAND STUCK ON ASSIGNED => ' . $productsRestoredCount . ' Products have been restored to "not-started" status: ' . $productsRestoredList);

		$this->line('--- Summary ---');
		$this->line('Deadline Date Applied: ' . $deadlineString . '.');
		$this->info('  ✅ ' . $productsRestoredCount . ' Products have been restored to "not-started" status.');
		$this->line('  IDs restored to "not-started": ' . $productsRestoredList);
		$this->line('----------------');
	}
}
