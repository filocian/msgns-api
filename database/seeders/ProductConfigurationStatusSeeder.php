<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProductConfigurationStatus;
use Illuminate\Database\Seeder;

final class ProductConfigurationStatusSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 */
	public function run(): void{
		ProductConfigurationStatus::create([
			'status_code' => ProductConfigurationStatus::$STATUS_NOT_STARTED,
			'description' => 'configuration not started'
		]);

		ProductConfigurationStatus::create([
			'status_code' => ProductConfigurationStatus::$STATUS_ASSIGNED,
			'description' => 'assigned to user'
		]);

		ProductConfigurationStatus::create([
			'status_code' => ProductConfigurationStatus::$STATUS_TARGET_SET,
			'description' => 'product target set'
		]);

		ProductConfigurationStatus::create([
			'status_code' => ProductConfigurationStatus::$STATUS_BUSINESS_SET,
			'description' => 'product target set'
		]);

		ProductConfigurationStatus::create([
			'status_code' => ProductConfigurationStatus::$STATUS_COMPLETED,
			'description' => 'configuration completed'
		]);
	}
}