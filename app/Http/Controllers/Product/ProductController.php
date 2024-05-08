<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Exceptions\Product\ProductNotFoundException;
use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Product\ActivateProductRequest;
use App\Http\Requests\Product\ConfigureProductRequest;
use App\Http\Requests\Product\RegisterProductRequest;
use App\Http\Requests\Product\RenameProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\UseCases\Product\Activation\ActivateUC;
use App\UseCases\Product\Activation\DeactivateUC;
use App\UseCases\Product\Assignment\AssignToCurrentUserUC;
use App\UseCases\Product\Assignment\AssignToUserUC;
use App\UseCases\Product\Configuration\ConfigureUC;
use App\UseCases\Product\Configuration\RenameUC;
use App\UseCases\Product\Filtering\FindByCurrentUserUC;
use App\UseCases\Product\Filtering\FindByIdUC;
use App\UseCases\Product\Registration\RegisterProductUC;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class ProductController extends Controller
{
	public function __construct(
		private readonly ActivateUC $ActivateProductUC,
		private readonly DeactivateUC $DeactivateProductUC,
		private readonly AssignToUserUC $AssignToUserUc,
		private readonly AssignToCurrentUserUC $AssignToCurrentUserUC,
		private readonly FindByIdUC $FindProductByIdUC,
		private readonly FindByCurrentUserUC $FindProductByLoggedUserUC,
		private readonly ConfigureUC $ConfigureUC,
		private readonly RenameUC $RenameUC,
		private readonly RegisterProductUC $RegisterProductUC
	) {}

	public function hello()
	{
		return HttpJson::OK('hello nfc');
	}

	public function activate(ActivateProductRequest $request, int $id): JsonResponse
	{
		$response = $this->ActivateProductUC->run([
			'id' => $id,
		]);

		return HttpJson::OK($response->wrapped('product'), Response::HTTP_CREATED);
	}

	public function deactivate(ActivateProductRequest $request, int $id): JsonResponse
	{
		$response = $this->DeactivateProductUC->run([
			'id' => $id,
		]);

		return HttpJson::OK($response->wrapped('product'), Response::HTTP_CREATED);
	}

	public function assign(int $id, int $userId): JsonResponse
	{
		$product = $this->AssignToUserUc->run([
			'id' => $id,
		]);

		return HttpJson::OK($product->wrapped('product'), Response::HTTP_CREATED);
	}

	public function assignToCurrentUser(int $id, string $password): JsonResponse
	{
		$product = $this->AssignToCurrentUserUC->run([
			'id' => $id,
			'password' => $password,
		]);

		return HttpJson::OK($product->wrapped('product'), Response::HTTP_CREATED);
	}

	public function register(RegisterProductRequest $request, int $id, string $password): JsonResponse
	{
		$product = $this->RegisterProductUC->run([
			'id' => $id,
			'password' => $password,
		]);

		return HttpJson::OK($product->wrapped('product'), Response::HTTP_CREATED);
	}

	public function findById(Request $request, string $id): JsonResponse
	{
		$product = $this->FindProductByIdUC->run([
			'id' => (int) $id,
		]);

		return HttpJson::OK($product->wrapped('product'));
	}

	/**
	 * Display a listing of current user's products.
	 *
	 * @throws ProductNotFoundException
	 */
	public function mine(): JsonResponse
	{
		$products = $this->FindProductByLoggedUserUC->run();
		return HttpJson::OK($products->wrapped('products'));
	}

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(StoreProductRequest $request)
	{
		//
	}

	/**
	 * Rename a product.
	 */
	public function rename(RenameProductRequest $request, int $productId): JsonResponse
	{
		$product = $this->RenameUC->run([
			'id' => $productId,
			'name' => $request->input('name'),
		]);

		return HttpJson::OK($product->wrapped('product'));
	}


	/**
	 * Update the specified resource in storage.
	 */
	public function configure(ConfigureProductRequest $request, int $productId): JsonResponse
	{
		$product = $this->ConfigureUC->run([
			'id' => $productId,
			'configuration' => $request->input('configuration'),
		]);

		return HttpJson::OK($product->wrapped('product'));
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(string $nfcId)
	{
		//
	}
}
