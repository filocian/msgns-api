<?php

declare(strict_types=1);

namespace App\UseCases\Product\Stats;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\Services\Product\ProductService;

final readonly class UsageOverviewUC implements UseCaseContract
{
	public function __construct(private ProductService $productService) {}

	public function run(mixed $data = null, ?array $opts = null)
	{
		$userId = $data['user_id'];

		$this->productService->testDynamoDB();

		return $this->productService->getProductUsageOverview($userId);
	}
}
