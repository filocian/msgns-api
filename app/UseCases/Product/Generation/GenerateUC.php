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
	public function run(mixed $data = null, ?array $opts = null): string
	{
		$productsToGenerate = $data;

		$productTypeTemplates = $this->resolveProductTemplates($productsToGenerate);
		$lastId = DB::table('products')->max('id');
		$newProducts = [];

		foreach ($productsToGenerate as $productType) {
			$template = $productTypeTemplates->find($productType['typeId']);
			$quantity = $productType['quantity'];
			for ($x = 0; $x < $quantity; $x++) {
				$lastId += 1;
				$productPassword = $this->generateProductPassword();
				$productUrl = $this->generateProductURL($lastId, $productPassword);
				$newProducts[] = $this->buildProduct($productType['typeId'], $template, $productPassword, $productUrl);
			}
		}

		DB::table('products')->insert($newProducts);

		return 'ok';
	}

	private function resolveProductTemplates(array $productsToGenerate): Collection
	{
		$productTypesId = array_map(function ($product) {
			return $product['typeId'];
		}, $productsToGenerate);

		return ProductType::findByMultipleIds($productTypesId);
	}
	private function generateProductURL(int $productId, string $password): string
	{
		return env('FRONT_URL', 'http://localhost:3000') . '/product/' . $productId . '/register/' . $password;
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
		string $productUrl
	): array {
		$now = Carbon::now();
		return [
			'product_type_id' => $productTypeId,
			'config' => json_encode(array_merge(
				$productTemplate->config_template,
				[
					'password' => $productPassword,
					'target' => $productUrl,
				]
			)),
			'name' => $productTemplate->name,
			'description' => $productTemplate->description,
			'created_at' => $now,
			'updated_at' => $now,
		];
	}
}
