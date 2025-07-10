<?php

declare(strict_types=1);

namespace App\UseCases\Product\Configuration;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\Product\CloneProductService;
use App\Models\Product;

final readonly class CloneFromCommonProductUC implements UseCaseContract
{
	public function __construct(private CloneProductService $cloneProductService) {}

	/**
	 * UseCase: Clone the configuration (target_url, name and business data from a given product)
	 *
	 * @param array{product_id: int, candidate_id: int}|null $data
	 * @param array|null $opts
	 * @return ProductDto
	 */
	public function run(mixed $data = null, ?array $opts = null): ProductDto
	{
		$productId = $data['product_id'];
		$candidateId = $data['candidate_id'];
		$productDto = ProductDto::fromModel(Product::findById($productId));
		$candidateDto = ProductDto::fromModel(Product::findById($candidateId));

		return $this->cloneProductService->cloneProduct($productDto, $candidateDto);
	}
}
