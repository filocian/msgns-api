<?php

declare(strict_types=1);

namespace App\UseCases\Product\Fancelet\LogicByType;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\Services\Product\Fancelet\FanceletService;

final class YogaUC implements UseCaseContract
{
	public function __construct(private FanceletService $fanceletService) {}

	public function run(mixed $data = null, ?array $opts = null)
	{
		$productId = $data['product_id'];
		$productPassword = $data['password'];

		return $this->fanceletService->getFanceletAvailableVideos($productId, $productPassword);
	}
}
