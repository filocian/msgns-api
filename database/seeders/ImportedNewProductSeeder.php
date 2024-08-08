<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Nette\FileNotFoundException;

final class ImportedNewProductSeeder extends Seeder
{
	private string $table = 'products';

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		$filePath = database_path('importer/data/new_products.json');

		if (!file_exists($filePath)) {
			throw new FileNotFoundException();
		}

		$seedFile = file_get_contents($filePath);
		$importedProducts = json_decode($seedFile);
		$products = [];

		foreach ($importedProducts as $product) {
			$products[] = [
				'id' => $product->id,
				'product_type_id' => $product->product_type_id,
				'user_id' => $product->user_id == 0 ? null : $product->user_id,
				'model' => $product->model,
				'password' => $product->password,
				'target_url' => $product->target_url,
				'usage' => $product->usage,
				'name' => $product->name,
				'configuration_status' => $product->configuration_status,
				'description' => $product->description,
				'active' => boolval($product->active),
				'created_at' => $product->created_at,
			];
		}

		$chunks = array_chunk($products, 1000);

//		DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

		foreach ($chunks as $chunk) {
			Product::insert($chunk);
		}

//		DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
//
//		$maxId = DB::table($this->table)->max('id');
//		DB::statement("ALTER TABLE $this->table AUTO_INCREMENT = " . ($maxId + 1));
	}
}
