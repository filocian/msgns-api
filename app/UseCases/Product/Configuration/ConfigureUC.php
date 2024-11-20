<?php

declare(strict_types=1);

namespace App\UseCases\Product\Configuration;

use App\Exceptions\Product\InvalidProductTypeException;
use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\MixPanel\MPLogger;
use App\Infrastructure\Services\Product\ProductService;
use App\Models\Product;
use App\Models\ProductConfigurationStatus;
use App\Models\ProductType;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class ConfigureUC implements UseCaseContract
{
	public function __construct(private ProductService $productService, private MPLogger $mpLogger) {}

	/**
	 * UseCase: Configure a single product based on its id
	 *
	 * @param array{id: int, target_url: string}|null $data
	 * @param array|null $opts
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 * @throws InvalidProductTypeException
	 */
	public function run(mixed $data = null, ?array $opts = null): ProductDto
	{
		$productId = $data['id'];
		$config = $data['target_url'] ?? null;

		return $this->configureProduct($productId, $config);
	}

	/**
	 * Configure a single product based on its id
	 *
	 * @param int $productId
	 * @param string $target_url
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 * @throws InvalidProductTypeException
	 */
	private function configureProduct(int $productId, string $target_url): ProductDto
	{
		try {
			$product = Product::findById($productId);

			$this->mpLogger->info('PRODUCT_CONFIGURATION', 'PRODUCT CONFIGURATION APPLIED', 'product configuration applied', [
				'product_id' => $productId,
				'target_url' => $target_url,
			]);
		} catch (ModelNotFoundException $e) {
			$this->mpLogger->error(
				'PRODUCT_CONFIGURATION',
				'ERROR APPLYING PRODUCT CONFIGURATION',
				'product configuration error',
				[
					'product_id' => $productId,
					'target_url' => $target_url,
					'exception_message' => $e->getMessage(),
				]
			);

			throw new ProductNotFoundException();
		}

		try {
			$productType = ProductType::findById($product->product_type_id);
		} catch (ModelNotFoundException $e) {
			$this->mpLogger->error('PRODUCT_RESET', 'ERROR APPLYING PRODUCT CONFIGURATION', 'invalid product type', [
				'product_id' => $productId,
				'target_url' => $target_url,
				'exception_message' => $e->getMessage(),
			]);

			throw new InvalidProductTypeException();
		}

		$configStatus = $this->productService
			->resolveConfigurationStatus($product, ProductConfigurationStatus::$STATUS_TARGET_SET);

		$config = [
			'target_url' => $target_url,
			'configuration_status' => $configStatus,
		];

		$product->update($config);

		$product->refresh();

		return ProductDto::fromModel($product);
	}
}
