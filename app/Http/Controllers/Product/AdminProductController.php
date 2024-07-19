<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Exceptions\Product\ProductNotFoundException;
use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Product\AssignToUserRequest;
use App\Http\Requests\Product\RegisterProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\UseCases\Product\Activation\ActivateUC;
use App\UseCases\Product\Activation\DeactivateUC;
use App\UseCases\Product\Assignment\AssignToCurrentUserUC;
use App\UseCases\Product\Assignment\AssignToUserUC;
use App\UseCases\Product\Filtering\FindByCurrentUserUC;
use App\UseCases\Product\Filtering\FindByIdUC;
use App\UseCases\Product\Listing\ProductListUC;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AdminProductController extends Controller
{
	public function __construct(
		private readonly ActivateUC $ActivateProductUC,
		private readonly DeactivateUC $DeactivateProductUC,
		private readonly AssignToUserUC $AssignToUserUC,
		private readonly FindByIdUC $FindProductByIdUC,
	) {}

	public function hello()
	{
		return HttpJson::OK('hello nfc');
	}

	public function activate(int $id): JsonResponse
	{
		$response = $this->ActivateProductUC->run([
			'id' => $id,
		]);

		return HttpJson::OK($response, Response::HTTP_CREATED);
	}

	public function deactivate(int $id): JsonResponse
	{
		$response = $this->DeactivateProductUC->run([
			'id' => $id,
		]);

		return HttpJson::OK($response, Response::HTTP_CREATED);
	}

	public function assignToUser(AssignToUserRequest $request, int $id, string $userId): JsonResponse
	{
		$product = $this->AssignToUserUC->run([
			'productId' => $id,
			'userId' => $userId,
		]);

		return HttpJson::OK($product->wrapped('product'), Response::HTTP_CREATED);
	}

	public function findById(Request $request, int $id): JsonResponse
	{
		$response = $this->FindProductByIdUC->run([
			'id' => $id,
		]);

		return HttpJson::OK($response);
	}

}
