<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Exceptions\Product\ProductNotFoundException;
use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Product\AssignToUserRequest;
use App\Http\Requests\User\OnlyAdminRequest;
use App\UseCases\Product\Activation\ActivateUC;
use App\UseCases\Product\Activation\DeactivateUC;
use App\UseCases\Product\Assignment\AssignToUserUC;
use App\UseCases\Product\Configuration\ResetUC;
use App\UseCases\Product\Filtering\FindByIdUC;
use App\UseCases\Product\Grouping\RemoveProductLinkUC;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class AdminProductController extends Controller
{
	public function __construct(
		private readonly ActivateUC $ActivateProductUC,
		private readonly DeactivateUC $DeactivateProductUC,
		private readonly AssignToUserUC $AssignToUserUC,
		private readonly RemoveProductLinkUC $RemoveProductLinkUC,
		private readonly FindByIdUC $FindProductByIdUC,
		private readonly ResetUC $resetUC,
	) {}

	public function hello()
	{
		return HttpJson::OK('hello nfc');
	}

	public function activate(OnlyAdminRequest $request, int $id): JsonResponse
	{
		$response = $this->ActivateProductUC->run([
			'id' => $id,
		]);

		return HttpJson::OK($response, Response::HTTP_CREATED);
	}

	public function deactivate(OnlyAdminRequest $request, int $id): JsonResponse
	{
		$response = $this->DeactivateProductUC->run([
			'id' => $id,
		]);

		return HttpJson::OK($response, Response::HTTP_CREATED);
	}

	public function assignToUser(AssignToUserRequest $request, int $id): JsonResponse
	{
		$email = $request->input('email');
		$product = $this->AssignToUserUC->run([
			'productId' => $id,
			'email' => $email,
		]);

		return HttpJson::OK($product->wrapped('product'), Response::HTTP_CREATED);
	}

	public function findById(OnlyAdminRequest $request, int $id): JsonResponse
	{
		$response = $this->FindProductByIdUC->run([
			'id' => $id,
		]);

		return HttpJson::OK($response);
	}

	public function removeProductLink(OnlyAdminRequest $request, int $child_id): JsonResponse
	{
		$product = $this->RemoveProductLinkUC->run([
			'childId' => $child_id,
		]);

		return HttpJson::OK($product->wrapped('product'));
	}

	/**
	 * Reset a product to not-started status. Only designed for types:
	 * Google Review, Instagram, Info, Facebook, Tiktok, Youtube, Whatsapp
	 *
	 * @param OnlyAdminRequest $request
	 * @param int $productId
	 * @return JsonResponse
	 * @throws ProductNotFoundException
	 */
	public function resetProduct(OnlyAdminRequest $request, int $productId): JsonResponse
	{
		$product = $this->resetUC->run([
			'id' => $productId,
		]);

		return HttpJson::OK($product->wrapped('product'));
	}
}
