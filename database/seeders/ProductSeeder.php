<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProductType;
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
		$staticProducts = StaticProductTypes::all();
		$productList = [];

		/** @var array<string, int> $typeIdByCode */
		$typeIdByCode = ProductType::all()->pluck('id', 'code')->toArray();

		$id = 1;
		foreach ($staticProducts as $static) {
			$typeId = $typeIdByCode[$static['name']] ?? null;
			if ($typeId === null) {
				continue;
			}
			$productList[] = [
				'id' => $id++,
				'product_type_id' => $typeId,
				'model' => $static['primary_model'],
				'password' => '123456',
				'name' => $static['name'],
				'description' => $static['description'],
				'usage' => 0,
				'active' => false,
				'configuration_status' => 'not-started',
				'created_at' => $now,
				'updated_at' => $now,
			];
		}

		return $productList;
	}
}
