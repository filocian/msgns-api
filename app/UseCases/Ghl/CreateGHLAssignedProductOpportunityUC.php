<?php

declare(strict_types=1);

namespace App\UseCases\Ghl;

use App\Events\ProductAssignedEvent;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\GHL\GhlService;
use App\Models\Product;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

final readonly class CreateGHLAssignedProductOpportunityUC implements UseCaseContract
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

		$contactId = $this->ghlService->resolveContactIdFromProductDto($productDto);

		$response = $this->ghlService->createProductAssignedOpportunity($productDto);
		event(new ProductAssignedEvent($product));

		Log::info(json_encode(['contact_id' => $contactId]));

		return $response;
	}
}
