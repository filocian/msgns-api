<?php

declare(strict_types=1);

namespace App\UseCases\Product\Configuration;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Models\Product;

final readonly class SetConfigStatusUC implements UseCaseContract
{
	public function __construct() {}

	/**
	 * UseCase: Configure a single product based on its id
	 *
	 * @param array{productId: int, status: string}|null $data
	 * @param array|null $opts
	 * @return ProductDto
	 */
	public function run(mixed $data = null, ?array $opts = null): ProductDto
	{
		$productId = $data['productId'];
		$status = $data['status'];
		$product = Product::findById($productId);

		$product->update([
			'configuration_status' => $status
		]);

		return ProductDto::fromModel($product);
	}
}
