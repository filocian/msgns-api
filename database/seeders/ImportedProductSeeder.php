<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Nette\FileNotFoundException;

final class ImportedProductSeeder extends Seeder
{
	private string $table = 'products';
	private array | null $productIdentificationCache = null;

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		$filePath = database_path('importer/data/products.json');

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
				'user_id' => $product->user_id,
				'target_url' => $product->target_url,
				'password' => $product->password,
				//'usage' => $product->usage,
				'name' => $product->name,
				'description' => $product->description,
				'active' => boolval($product->active),
				'created_at' => $product->created_at,
			];
		}

		$chunks = array_chunk($products, 1000);

		DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

		foreach ($chunks as $chunk) {
			Product::insert($chunk);
		}

		DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

		$maxId = DB::table($this->table)->max('id');
		DB::statement("ALTER TABLE $this->table AUTO_INCREMENT = " . ($maxId + 1));
	}
}
