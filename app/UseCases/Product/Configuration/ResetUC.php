<?php

declare(strict_types=1);

namespace App\UseCases\Product\Configuration;

use App\Exceptions\Product\InvalidProductTypeException;
use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\DynamoDb\DynamoDbService;
use App\Infrastructure\Services\MixPanel\MPLogger;
use App\Infrastructure\Services\Product\ProductService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class ResetUC implements UseCaseContract
{
	public function __construct(
		private ProductService $productService,
		private DynamoDbService $dynamoDbService,
		private MPLogger $mpLogger,
	) {}

	/**
	 * UseCase: Reset a classic product to not-started status
	 *
	 * @param array{id: int}|null $data
	 * @param array|null $opts
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 * @throws Exception
	 */
	public function run(mixed $data = null, ?array $opts = null): ProductDto
	{
		$productId = (int) $data['id'];

		try {
			$productDto = $this->productService->resetProduct($productId);

			$this->mpLogger->info('PRODUCT_RESET', 'PRODUCT RESET OCCURRED', 'product reset done', [
				'product_id' => $productId,
			]);
		} catch (ModelNotFoundException $e) {
			$this->mpLogger->error('PRODUCT_RESET', 'ERROR AT PRODUCT RESET OCCURRED', 'product not found', [
				'product_id' => $productId,
				'exception_message' => $e->getMessage(),
			]);

			throw new ProductNotFoundException();
		} catch (InvalidProductTypeException $e) {
			$this->mpLogger->error('PRODUCT_RESET', 'ERROR AT PRODUCT RESET OCCURRED', 'invalid product type', [
				'product_id' => $productId,
				'exception_message' => $e->getMessage(),
			]);

			throw new InvalidProductTypeException();
		}

		$this->dynamoDbService->deleteProductStats($productId);

		return $productDto;
	}
}
