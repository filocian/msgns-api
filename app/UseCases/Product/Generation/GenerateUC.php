<?php

declare(strict_types=1);

namespace App\UseCases\Product\Generation;

use App\Helpers\StringHelpers;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Models\ProductType;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Random\RandomException;

final readonly class GenerateUC implements UseCaseContract
{
	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param mixed $data
	 * @param array|null $opts
	 * @return array
	 * @throws RandomException
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
			$size = $productType['size'] ?? null;
			$newProductsURLs[$template->code] = [];

			for ($x = 0; $x < $quantity; $x++) {
				$lastId += 1;
				$product = $this->buildProduct($productType['typeId'], $template, $lastId, false, $size);
				$newProducts[] = $product;
				$newProductsURLs[$template->code][] = env(
					'FRONT_URL'
				) . '/product/' . $lastId . '/redirect/' . $product['password'];

				// Doble cara
				if ($template->secondary_model !== null) {
					$lastId += 1;
					$product = $this->buildProduct($productType['typeId'], $template, $lastId, true, $size);
					$newProducts[] = $product;
					$newProductsURLs[$template->code][] = env(
						'FRONT_URL'
					) . '/product/' . $lastId . '/redirect/' . $product['password'];
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

	/**
	 * @throws Exception|RandomException
	 */
	private function generateUuid(): string
	{
		try {
			return StringHelpers::generateUuidV4();
		} catch (RandomException $e) {
			throw new RandomException('Error generating UUID');
		} catch (Exception $e) {
			throw new Exception('Error generating UUID');
		}
	}

	/**
	 * @throws RandomException
	 */
	private function buildProduct(
		int $productTypeId,
		Model $productTemplate,
		int $lastId,
		bool $isSecondaryModel = false,
		?string $size = null
	): array {
		$now = Carbon::now();
		$model = $isSecondaryModel ? $productTemplate->secondary_model : $productTemplate->primary_model;
		$productPassword = str_starts_with($productTemplate->code, 'B-')
			? $this->generateUuid()
			: $this->generateProductPassword();
		$name = str_starts_with($productTemplate->code, 'B-')
			? $productTemplate->name
			: $model;

		return [
			'product_type_id' => $productTypeId,
			'model' => $model,
			'password' => $productPassword,
			'name' => $name . ' (' . $lastId . ')',
			'description' => $productTemplate->description,
			'active' => 1,
			'created_at' => $now,
			'updated_at' => $now,
			'size' => $size,
		];
	}
}
