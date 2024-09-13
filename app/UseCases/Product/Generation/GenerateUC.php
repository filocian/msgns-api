<?php

declare(strict_types=1);

namespace App\UseCases\Product\Generation;

use App\Helpers\StringHelpers;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Models\ProductType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final readonly class GenerateUC implements UseCaseContract
{
	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param mixed $data
	 * @param array|null $opts
	 * @return string
	 */
	public function run(mixed $data = null, ?array $opts = null): array
	{
		$productsToGenerate = $data;

		$productTypeTemplates = $this->resolveProductTemplates($productsToGenerate);
		$lastId = DB::table('products')->max('id');
		$newProducts = [];
		$newProductsURLs = [];

		foreach ($productsToGenerate as $productType) {
			$template = $productTypeTemplates->find($productType['typeId']);
			$quantity = $productType['quantity'];
			$newProductsURLs[$template->code] = [];

			for ($x = 0; $x < $quantity; $x++) {
				$lastId += 1;
				$productPassword = $this->generateProductPassword();
				$newProducts[] = $this->buildProduct($productType['typeId'], $template, $productPassword, $lastId);
				$newProductsURLs[$template->code][] = env('FRONT_URL') . '/product/' . $lastId . '/redirect/' . $productPassword;

				if ($template->secondary_model !== null) {
					$lastId += 1;
					$newProducts[] = $this->buildProduct($productType['typeId'], $template, $productPassword, $lastId, true);
					$newProductsURLs[$template->code][] = env('FRONT_URL') . '/product/' . $lastId . '/redirect/' . $productPassword;
				}
			}
		}

		$chunks = array_chunk($newProducts, 1000);

		foreach ($chunks as $chunk) {
			DB::table('products')->insert($chunk);
		}

		return [
			'new_products_count' => count($newProducts),
			'product_list' => $newProductsURLs,
		];
	}

	private function resolveProductTemplates(array $productsToGenerate): Collection
	{
		$productTypesId = array_map(function ($product) {
			return $product['typeId'];
		}, $productsToGenerate);

		return ProductType::findByMultipleIds($productTypesId);
	}

	private function generateProductPassword(?int $length = null): string
	{
		if ($length) {
			return StringHelpers::generateAlphaNumericString($length);
		}

		return StringHelpers::generateAlphaNumericString(env('DEFAULT_PRODUCT_PASSWORD_LENGTH', 12));
	}
	private function buildProduct(
		int $productTypeId,
		Model $productTemplate,
		string $productPassword,
		int $lastId,
		bool $isSecondaryModel = false
	): array {
		$now = Carbon::now();
		$model = $isSecondaryModel ? $productTemplate->secondary_model : $productTemplate->primary_model;
		return [
			'product_type_id' => $productTypeId,
			'model' => $model,
			'password' => $productPassword,
			'name' => $model . ' (' . $lastId . ')',
			'description' => $productTemplate->description,
			'active' => 1,
			'created_at' => $now,
			'updated_at' => $now,
		];
	}
}
