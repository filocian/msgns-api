<?php

declare(strict_types=1);

namespace App\UseCases\Product\Businesses;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\Product\ProductService;
use App\Models\Product;
use App\Models\ProductBusiness;
use App\Models\ProductConfigurationStatus;

final readonly class AddBusinessUC implements UseCaseContract
{
	public function __construct(private ProductService $productService) {}

	/**
	 * UseCase: Assign a product to given user
	 *
	 * @param mixed $data
	 * @param array|null $opts
	 * @return ProductDto
	 */
	public function run(mixed $data = null, ?array $opts = null): ProductDto
	{
		$businessData = [
			'types' => $data['types'] ?? [],
			'place_types' => $data['placeTypes'] ?? [],

		];

		if (isset($data['name'])) {
			$businessData['name'] = $data['name'];
		}
		if (isset($data['notBusiness'])) {
			$businessData['not_a_business'] = $data['notBusiness'];
		}

		if (isset($data['size'])) {
			$businessData['size'] = $data['size'];
		}

		$businessData['user_id'] = $data['userId'];

		$business = ProductBusiness::updateOrCreate(
			[
				'product_id' => $data['productId'],
				//				'user_id' => $data['userId'],
			],
			$businessData
		);

		$productId = $data['productId'];
		$product = Product::findById($productId);
		$configStatus = $this->productService
			->resolveConfigurationStatus($product, ProductConfigurationStatus::$STATUS_BUSINESS_SET);
		$product->update(['configuration_status' => $configStatus]);
		$product->refresh();


		return ProductDto::fromModel($product);
	}
}
