<?php

declare(strict_types=1);

namespace App\UseCases\Product\Fancelet\Pairing;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductTypeDto;
use App\Models\Product;
use App\Models\ProductType;
use Exception;

final readonly class AnonymousFanceletPairingUC implements UseCaseContract
{
	public function __construct() {}

	/***
	 * @param array{product_type_id: int, pairs: array} $data
	 * @param array|null $opts
	 * @return null|array
	 */
	public function run(mixed $data = null, ?array $opts = null): array|null
	{
		$productTypeId = $data['product_type_id'];
		$pairs = $data['pairs'];
		$paired = 0;
		$failed = [];
		$productTypeCode = ProductTypeDto::fromModel(ProductType::findById($productTypeId))->code;

		if (!str_starts_with($productTypeCode, 'B-')) {
			return null;
		}

		foreach ($pairs as $master => $slaves) {
			foreach ($slaves as $slave) {
				try {
					Product::findById((int) $slave)->update(['linked_to_product_id' => $master]);
					$paired++;
				} catch (Exception $exception) {
					$failed[] = [$master => $slave];
				}
			}
		}

		return ['paired' => $paired, 'failed' => $failed];
	}
}
