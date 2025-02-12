<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class LoveFanceletProdTestingPairsSeeder extends Seeder
{
	protected $table = 'products';

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		$pairs = [[184260, 184261], [184257, 184255], ];

		foreach ($pairs as $pair) {
			DB::table($this->table)->where(['id' => $pair[0]])->update(['linked_to_product_id' => $pair[1]]);
		}
	}
}
