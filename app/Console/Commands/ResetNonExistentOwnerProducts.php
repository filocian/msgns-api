<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class ResetNonExistentOwnerProducts extends Command
{
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
		Log::info('Reset non-existent owner-products');
	}
}
