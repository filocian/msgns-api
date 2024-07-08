<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Static\Product\StaticProductTypes;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ProductTypesSeeder extends Seeder
{
	protected $table = 'product_types';

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		DB::table($this->table)
			->insert($this->nfcTypes());
	}

	private function nfcTypes(): array
	{
		$now = Carbon::now();
		return array_map(function (array $productType) use ($now) {
			return [
				'code' => $productType['code'],
				'name' => $productType['code'],
				'description' => $productType['description'],
				'image_ref' => $productType['code'],
				'created_at' => $now,
				'updated_at' => $now,
			];
		}, StaticProductTypes::all());
	}
}
