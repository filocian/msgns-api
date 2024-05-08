<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Static\Product\StaticProductTypes;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ProductSeeder extends Seeder
{
	protected $table = 'products';

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		DB::table($this->table)
			->insert($this->products());
	}

	private function products(): array
	{
		$now = Carbon::now();
		$productsQty = 1;
		$staticProducts = StaticProductTypes::all();
		$productList = [];

		for ($y = 0; $y < count($staticProducts); $y++) {
			for ($x = 0; $x < $productsQty; $x++) {
				$product = array_merge($staticProducts[$y], [
					'product_type_id' => $y + 1,
					'created_at' => $now,
					'updated_at' => $now,
				]);

				unset($product['code']);
				$product['config'] = json_encode($product['config']);
				$productList[] = $product;
			}
		}

		return $productList;
	}
}
