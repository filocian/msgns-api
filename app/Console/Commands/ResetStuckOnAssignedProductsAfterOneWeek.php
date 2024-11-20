<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class ResetStuckOnAssignedProductsAfterOneWeek extends Command
{
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
		Log::info('Reset stuck on assigned products after 7 days');
	}
}
