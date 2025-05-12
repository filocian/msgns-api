<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class UpdateInnerPeaceAndYogaProductTypesSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		DB::table('product_types')->where([
			'id' => 24,
		])->update(['code' => 'F-YO-B1-IW-185-013-04']);

		DB::table('product_types')->where([
			'id' => 29,
		])->update([
			'code' => 'F-IP-B1-CS-185-013-07',
			'name' => 'Inner Peace',
			'description' => 'Inner Peace program for registered users',
		]);
	}
}
