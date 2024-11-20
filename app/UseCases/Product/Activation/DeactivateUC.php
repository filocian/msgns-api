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

final readonly class DeactivateUC implements UseCaseContract
{
	public function __construct(private MPLogger $mpLogger) {}

	/**
	 * Deactivate a product based on product id
	 *
	 * @param array{id: int}|null $data
	 * @param array|null $opts
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 */
	public function run(mixed $data = null, ?array $opts = null): ProductDto
	{
		$productId = $data['id'];

		return $this->deactivateProduct($productId);
	}

	/**
	 * Deactivates a product
	 *
	 * @param int $id
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 * @throws Exception
	 */
	private function deactivateProduct(int $id): ProductDto
	{
		try {
			$product = Product::findById($id,);

			$product->update([
				'active' => false,
			]);

			$product->refresh();

			$this->mpLogger->info('PRODUCT_DISABLED', 'PRODUCT DISABLED', 'product disabled', [
				'product_id' => $id,
				'active' => false,
			]);

			return ProductDto::fromModel($product);
		} catch (ModelNotFoundException $e) {
			$this->mpLogger->error('PRODUCT_DISABLED', 'ERROR PRODUCT DISABLED', 'product disabled', [
				'product_id' => $id,
				'exception_message' => $e->getMessage(),
			]);

			throw new ProductNotFoundException();
		}
	}
}
