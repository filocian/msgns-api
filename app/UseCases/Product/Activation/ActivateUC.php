<?php

declare(strict_types=1);

namespace App\UseCases\Product\Activation;

use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\MixPanel\MPLogger;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class ActivateUC implements UseCaseContract
{
	public function __construct(private MPLogger $mpLogger) {}

	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param array{id: int}|null $data
	 * @param array|null $opts
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 */
	public function run(mixed $data = null, ?array $opts = null): ProductDto
	{
		$productId = $data['id'];

		return $this->activateProduct($productId);
	}

	/**
	 * Activates a product
	 *
	 * @param int $productId
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 * @throws Exception
	 */
	private function activateProduct(int $productId): ProductDto
	{
		try {
			$product = Product::findById($productId);

			$product->update([
				'active' => true,
			]);

			$product->refresh();

			$this->mpLogger->info('PRODUCT_ENABLED', 'PRODUCT ENABLED', 'product enabled', [
				'product_id' => $productId,
				'active' => true,
			]);

			return ProductDto::fromModel($product);
		} catch (ModelNotFoundException $e) {
			$this->mpLogger->error('PRODUCT_ENABLED', 'ERROR PRODUCT ENABLED', 'product enabled', [
				'product_id' => $productId,
				'exception_message' => $e->getMessage(),
			]);

			throw new ProductNotFoundException();
		}
	}
}
