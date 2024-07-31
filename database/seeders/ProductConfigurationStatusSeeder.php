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
			'status_code' => 'not-started',
			'description' => 'configuration not started'
		]);

		ProductConfigurationStatus::create([
			'status_code' => 'assigned',
			'description' => 'assigned to user'
		]);

		ProductConfigurationStatus::create([
			'status_code' => 'target_set',
			'description' => 'product target set'
		]);

		ProductConfigurationStatus::create([
			'status_code' => 'business_set',
			'description' => 'product target set'
		]);

		ProductConfigurationStatus::create([
			'status_code' => 'completed',
			'description' => 'configuration completed'
		]);
	}
}