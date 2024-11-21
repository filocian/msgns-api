<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Infrastructure\Services\MixPanel\MPLogger;
use App\Models\Product;
use App\UseCases\Product\Configuration\ResetUC;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

final class ResetStuckOnAssignedProductsAfterOneWeek extends Command
{
    public function __construct(private ResetUC $resetUC, private MPLogger $mpLogger){
        parent::__construct();
    }

	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'app:reset-stuck-on-assigned-products-after-one-week';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Resets products that are stuck on assigned status after 7 days';

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
        $deadline = Carbon::now()->addDays(7);
        $affectedProducts = [];

        $products = Product::where('configuration_status', 'assigned')
            ->where('assigned_at', '<=', $deadline)
            ->get();

        if(count($products ?? []) < 1){
            $this->mpLogger->info('ASSIGNED_PURGE', 'NO STUCK ON ASSIGNED PRODUCTS', 'no products stuck found', [
                'products_restarted' => $affectedProducts,
            ]);

            Log::info('COMMAND STUCK ON ASSIGNED => no products stuck found');

            return;
        }

        foreach ($products as $product) {
            $this->resetUC->run(['id' => $product->id]);
            $affectedProducts[] = $product->id;
        }

        $this->mpLogger->warn('ASSIGNED_PURGE', 'PURGED STUCK ON ASSIGNED PRODUCTS', 'products restarted', [
            'products_restarted' => $affectedProducts,
        ]);

        Log::alert('COMMAND STUCK ON ASSIGNED => reset stuck on assigned products after 7 days');
	}
}
