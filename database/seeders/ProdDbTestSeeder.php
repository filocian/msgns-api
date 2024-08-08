<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ProdDbTestSeeder extends Seeder
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
			ImportedNewProductSeeder::class
		]);
	}
}