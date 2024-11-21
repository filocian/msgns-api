<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Infrastructure\Services\MixPanel\MPLogger;
use App\Models\Product;
use App\UseCases\Product\Configuration\ResetUC;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

final class ResetNonExistentOwnerProducts extends Command
{
    public function __construct(private ResetUC $resetUC, private MPLogger $mpLogger){
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

        foreach ($products as $product) {
            //$this->resetUC->run(['id' => $product->id]);
            $affectedProducts[] = $product->id;
        }

        $this->mpLogger->info('PRODUCT_OWNER_INVALID_PURGE', 'PURGED STUC ON ASSIGNED PRODUCTS', 'products restarted', [
            'products_restarted' => $affectedProducts,
        ]);

		Log::info('Reset non-existent owner-products');
	}
}
