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
			'description' => 'Product configuration not yet started',
			'label' => '0-Not Started'
		]);

		ProductConfigurationStatus::create([
			'status_code' => ProductConfigurationStatus::$STATUS_ASSIGNED,
			'description' => 'The product has been assigned to a user',
			'label' => '1-Assigned'
		]);

		ProductConfigurationStatus::create([
			'status_code' => ProductConfigurationStatus::$STATUS_TARGET_SET,
			'description' => 'The product has been set',
			'label' => '2-Target set'
		]);

		ProductConfigurationStatus::create([
			'status_code' => ProductConfigurationStatus::$STATUS_BUSINESS_SET,
			'description' => 'The product segmentation data (business) has been set',
			'label' => '3-Business set'
		]);

		ProductConfigurationStatus::create([
			'status_code' => ProductConfigurationStatus::$STATUS_COMPLETED,
			'description' => 'Product configuration has been completed',
			'label' => '4-Completed'
		]);
	}
}