<?php

declare(strict_types=1);

namespace App\UseCases\Product\Configuration;

use App\DTO\ProductDto;
use App\Exceptions\Product\InvalidProductTypeException;
use App\Exceptions\Product\ProductNotFoundException;
use App\Exceptions\Product\ProductNotOwnedException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Models\Product;
use App\Models\ProductType;
use App\Services\Auth\AuthService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class ConfigureUC implements UseCaseContract
{
	public function __construct(
		private AuthService $authService,
	) {}

	/**
	 * UseCase: Configure a single product based on its id
	 *
	 * @param array{id: int, configuration: array}|null $data
	 * @param array|null $opts
	 * @return ProductDto
	 * @throws ProductNotFoundException|ProductNotOwnedException
	 * @throws InvalidProductTypeException
	 */
	public function run(mixed $data = null, ?array $opts = null): ProductDto
	{
		$productId = $data['id'];
		$config = $data['configuration'];

		return $this->configureProduct($productId, $config);
	}

	/**
	 * Configure a single product based on its id
	 *
	 * @param int $productId
	 * @param array $config
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 * @throws ProductNotOwnedException
	 * @throws InvalidProductTypeException
	 */
	private function configureProduct(int $productId, array $config): ProductDto
	{
		try {
			$product = Product::findById($productId);
		} catch (ModelNotFoundException $e) {
			throw new ProductNotFoundException();
		}

		try {
			$productType = ProductType::findById($product->product_type_id);
			$configTemplate = $productType->config_template;
		} catch (ModelNotFoundException $e) {
			throw new InvalidProductTypeException();
		}

		$userId = $this->authService->id();

		//TODO: add role permission for admins
		if ($userId !== $product->user_id) {
			throw new ProductNotOwnedException();
		}

		$config = array_merge($configTemplate, $config);

		$product->update([
			'config' => $config,
		]);

		$product->refresh();

		return ProductDto::fromModel($product);
	}
}
