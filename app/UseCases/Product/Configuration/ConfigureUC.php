<?php

declare(strict_types=1);

namespace App\UseCases\Product\Configuration;

use App\Exceptions\Product\InvalidProductTypeException;
use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Models\Product;
use App\Models\ProductType;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class ConfigureUC implements UseCaseContract
{
	public function __construct() {}

	/**
	 * UseCase: Configure a single product based on its id
	 *
	 * @param array{id: int, name:string|null, configuration: array}|null $data
	 * @param array|null $opts
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 * @throws InvalidProductTypeException
	 */
	public function run(mixed $data = null, ?array $opts = null): ProductDto
	{
		$productId = $data['id'];
		$name =$data['name'] ?? null;
		$config = $data['configuration'];
		$business = $data['business'] ?? null;

		return $this->configureProduct($productId, $name, $config, $business);
	}

	/**
	 * Configure a single product based on its id
	 *
	 * @param int $productId
	 * @param string|null $name
	 * @param array $config
	 * @param array|null $business
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 * @throws InvalidProductTypeException
	 */
	private function configureProduct(int $productId, string|null $name, array $config, ?array $business): ProductDto
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

		$config = [
			'config' => array_merge($configTemplate, $config)
		];

		if(isset($name)){
			$config['name'] = $name;
		}

		$product->update($config);

		$product->refresh();

		return ProductDto::fromModel($product);
	}
}
