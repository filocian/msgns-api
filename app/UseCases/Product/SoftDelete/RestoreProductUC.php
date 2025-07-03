<?php

declare(strict_types=1);

namespace App\UseCases\Product\SoftDelete;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Models\Product;

final readonly class RestoreProductUC implements UseCaseContract
{
	public function __construct() {}

	/**
	 * UseCase: Assign a product to current user and activates it
	 *
	 * @param array{product_id: int}|null $data
	 * @param array|null $opts
	 * @return ProductDto
	 */
	public function run(mixed $data = null, ?array $opts = null): ProductDto
	{
		$productId = $data['product_id'];
		$product = Product::findById($productId, true);
		$product->restore();
		$product->refresh();

		return ProductDto::fromModel($product);
	}
}
