<?php

declare(strict_types=1);

namespace App\UseCases\Product\Grouping;

use App\Exceptions\Product\InvalidProductTypeException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\MixPanel\MPLogger;
use App\Models\Product;

final class SetFanceletGroupUC implements UseCaseContract
{
	public function __construct(
		private MPLogger $mpLogger
	) {}

	/**
	 * UseCase: Link product to parent product
	 *
	 * @param array{parent_product_id: int, parent_product_password: string, children_product_ids: int[]} $data
	 * @param array|null $opts
	 * @return ProductDto
	 * @throws InvalidProductTypeException
	 */
	public function run(mixed $data = null, ?array $opts = []): int
	{
		$parentId = $data['parent_product_id'];
		$parentPassword = $data['parent_product_password'];
		$childrenIds = $data['children_product_ids'];
		$parentProduct = Product::findByConfigPair($parentId, 'password', $parentPassword);
		$parentProductDto = ProductDto::fromModel($parentProduct);

		if (!str_starts_with($parentProductDto->type->code, 'B-')) {
			throw new InvalidProductTypeException('not a fancelet product type');
		}

		$childrenProducts = array_map(function ($childId) {
			return Product::findById((int) $childId);
		}, $childrenIds);
		$parentProductDto = ProductDto::fromModel($parentProduct);
		$childrenProductsDto = [];


		foreach ($childrenProducts as $childProduct) {
			$childDto = ProductDto::fromModel($childProduct);
			if (!str_starts_with($childDto->type->code, 'B-')) {
				continue;
			}

			$childrenProductsDto[] = $childDto;
			$childProduct->setParentProduct($parentProductDto->id);
		}

		$this->mpLogger->info('FANCELET_GROUPING', 'GROUPED', 'products grouped', [
			'main_product' => $parentProductDto->id,
			'children_products' => array_map(function ($childProductDto) {
				return $childProductDto->id;
			}, $childrenProductsDto),
		]);

		return $parentProductDto->id;
	}
}
