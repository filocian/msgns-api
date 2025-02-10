<?php

declare(strict_types=1);

namespace App\UseCases\GHL;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\GHL\GhlService;
use App\Models\Product;
use App\Static\GHL\StaticGHLOpportunities;
use Illuminate\Http\Client\ConnectionException;

final readonly class CreateOpportunityUC implements UseCaseContract
{
	public function __construct(
		private GhlService $ghlService,
	) {}

	/**
	 * @param array{product: Product} $data
	 * @param array|null $opts
	 * @return string
	 * @throws ConnectionException
	 */
	public function run(mixed $data = null, ?array $opts = null)
	{
		$product = $data['product'];
		$productDto = ProductDto::fromModel($product);

		return $this->ghlService->createOrUpdateOpportunity($productDto, [
			'pipelineId' => StaticGHLOpportunities::$PRODUCT_PIPELINE_ID,
			'stageId' => StaticGHLOpportunities::$PRODUCT_ASSIGNED_STAGE_ID,
			'name' => $productDto->type->code . " - $productDto->model ($productDto->id)",
			'status' => 'open',
		]);
	}
}
