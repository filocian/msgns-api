<?php

declare(strict_types=1);

namespace App\UseCases\Product\Configuration;

use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\MixPanel\MPLogger;
use App\Infrastructure\Services\Product\ProductService;
use App\Models\Product;
use App\Models\ProductConfigurationStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class RenameUC implements UseCaseContract
{
	public function __construct(private ProductService $productService, private MPLogger $mpLogger) {}

	/**
	 * UseCase: Configure a single product based on its id
	 *
	 * @param array{id: int, name: string}|null $data
	 * @param array|null $opts
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 */
	public function run(mixed $data = null, ?array $opts = null): ProductDto
	{
		$productId = $data['id'];
		$name = $data['name'];

		return $this->configureProduct($productId, $name);
	}

	/**
	 * Rename a single product based on its id
	 *
	 * @param int $productId
	 * @param string $name
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 */
	private function configureProduct(int $productId, string $name): ProductDto
	{
		try {
			$product = Product::findById($productId);

			$this->mpLogger->info('PRODUCT_NAMING', 'PRODUCT NAMING', 'product name has been set', [
				'product_id' => $productId,
				'name' => $name,
			]);
		} catch (ModelNotFoundException $e) {
			$this->mpLogger->error('PRODUCT_RESET', 'ERROR AT PRODUCT RESET OCCURRED', 'invalid product type', [
				'product_id' => $productId,
				'exception_message' => $e->getMessage(),
			]);

			throw new ProductNotFoundException();
		}

		$configStatus = $this->productService
			->resolveConfigurationStatus($product, ProductConfigurationStatus::$STATUS_COMPLETED);

		$product->update([
			'name' => $name,
			'configuration_status' => $configStatus,
		]);

		$product->refresh();

		return ProductDto::fromModel($product);
	}
}
