<?php

declare(strict_types=1);

namespace App\UseCases\Product\Redirect;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Models\Product;

final readonly class ProductUsageUC implements UseCaseContract
{
	public function __construct()
	{
	}

	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param array{productModel: Product}|null $data
	 * @param array|null $opts
	 * @return void
	 */
	public function run(mixed $data = null, ?array $opts = null): void
	{
		$productModel = $data['productModel'];
		$productDto = ProductDto::fromModel($productModel);
		$productModel->usage = $productDto->usage + 1;
		$productModel->save();
	}
}
