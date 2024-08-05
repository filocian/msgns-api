<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductBusiness;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Nette\FileNotFoundException;

final class ImportedSegmentationSeeder extends Seeder
{
	private string $table = 'product_business';

	/**
	 * Run the database seeds.
	 */
	public function run(): void
	{
		$filePath = database_path('importer/data/segmentation.json');

		if (!file_exists($filePath)) {
			throw new FileNotFoundException();
		}

		$seedFile = file_get_contents(database_path('importer/data/segmentation.json'));
		$businessDataByUser = json_decode($seedFile);
		$productBusiness = [];

		foreach ($businessDataByUser as $userBusiness) {
			$productsByUser = Product::findProductsByUserId($userBusiness->id);

			if(count($userBusiness->businessTypes) == 0){
				continue;
			}

			$resolvedTypes = count($userBusiness->businessTypes) > 0? $userBusiness->businessTypes : [];

			if(count($resolvedTypes) > 0){
				$types = array_values(array_unique($resolvedTypes));
			} else {
				$types = [];
			}

			foreach($productsByUser as $product){
				$productBusiness[] = [
					'product_id' => $product->id,
					'user_id' => $userBusiness->id,
					'types' => json_encode($types),
					'created_at' => Carbon::now()->toDateTimeString(),
					'updated_at' => Carbon::now()->toDateTimeString(),
				];
			}
		}

		$chunks = array_chunk($productBusiness, 1000);

//		DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

		foreach ($chunks as $chunk) {
			ProductBusiness::insert($chunk);
		}

//		DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
//
//		$maxId = DB::table($this->table)->max('id');
//		DB::statement("ALTER TABLE $this->table AUTO_INCREMENT = " . ($maxId + 1));
	}
}
