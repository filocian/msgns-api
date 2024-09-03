<?php

declare(strict_types=1);

namespace App\UseCases\Product\Redirect;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\B4a\B4aService;
use App\Models\Product;

final readonly class ProductStatisticsUC implements UseCaseContract
{
	public function __construct(private B4aService $b4aService) {}

	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param array{productModel: Product}|null $data
	 * @param array|null $opts
	 * @return void
	 * @throws \Exception
	 */
	public function run(mixed $data = null, ?array $opts = null): void
	{
		$productModel = $data['productModel'];
		$productUsageObject = $this->b4aService->createProductUsageObject($productModel);
		$productUsageObject->save();
	}
}
