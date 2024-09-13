<?php

declare(strict_types=1);

namespace App\UseCases\Product\Redirect;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\Services\DynamoDb\DynamoDbService;
use App\Models\Product;
use Exception;

final readonly class ProductStatisticsUC implements UseCaseContract
{
	public function __construct(private DynamoDbService $dynamoDbService) {}

	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param array{productModel: Product}|null $data
	 * @param array|null $opts
	 * @return void
	 * @throws Exception
	 */
	public function run(mixed $data = null, ?array $opts = null): void
	{
		$productModel = $data['productModel'];
		$this->dynamoDbService->putProductUsage($productModel);
	}
}
