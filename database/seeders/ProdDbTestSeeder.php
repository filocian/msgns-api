<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class ProdDbTestSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		$this->call([
			ImportedUserSeeder::class,
			ImportedProductSeeder::class,
			ImportedSegmentationSeeder::class,
			ImportedWhatsappSeeder::class,
			ImportedNewProductSeeder::class,
		]);
	}
}
