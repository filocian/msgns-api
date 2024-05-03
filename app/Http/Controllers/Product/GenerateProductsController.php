<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Http\Contracts\HttpJson;
use App\Http\Requests\Product\GenerateProductsRequest;
use App\UseCases\Product\Generation\GenerateUC;
use Illuminate\Http\JsonResponse;

final readonly class GenerateProductsController
{
	public function __construct(
		private GenerateUC $GenerateProductsUC,
	) {}
	public function generateProducts(GenerateProductsRequest $request): JsonResponse
	{
		$request->validated();

		$response = $this->GenerateProductsUC->run($request->input('types'));

		return HttpJson::OK($response);
	}
}
