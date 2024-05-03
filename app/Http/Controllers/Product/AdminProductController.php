<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Exceptions\Product\ProductNotFoundException;
use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Product\RegisterProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\UseCases\Product\Activation\ActivateUC;
use App\UseCases\Product\Activation\DeactivateUC;
use App\UseCases\Product\Assignment\AssignToCurrentUserUC;
use App\UseCases\Product\Assignment\AssignToUserUC;
use App\UseCases\Product\Filtering\FindByCurrentUserUC;
use App\UseCases\Product\Filtering\FindByIdUC;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class AdminProductController extends Controller
{
	public function __construct(
		private readonly ActivateUC $ActivateProductUC,
		private readonly DeactivateUC $DeactivateProductUC,
		private readonly AssignToUserUC $AssignToUserUc,
		private readonly AssignToCurrentUserUC $AssignToCurrentUserUC,
		private readonly FindByIdUC $FindProductByIdUC,
		private readonly FindByCurrentUserUC $FindProductByLoggedUserUC
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

	public function assign(int $id, int $userId): JsonResponse
	{
		$response = $this->AssignToUserUc->run([
			'id' => $id,
		]);

		return HttpJson::OK($response, Response::HTTP_CREATED);
	}

	public function assignToCurrentUser(int $id, string $password): JsonResponse
	{
		$response = $this->AssignToCurrentUserUC->run([
			'id' => $id,
			'password' => $password,
		]);

		return HttpJson::OK($response, Response::HTTP_CREATED);
	}

	public function findById(Request $request, int $id): JsonResponse
	{
		$response = $this->FindProductByIdUC->run([
			'id' => $id,
		]);

		return HttpJson::OK($response);
	}


	/**
	 * Display a listing of current user's products.
	 *
	 * @throws ProductNotFoundException
	 */
	public function mine(): JsonResponse
	{
		$response = $this->FindProductByLoggedUserUC->run();

		return HttpJson::OK($response);
	}

	/**
	 * Show the form for creating a new resource.
	 */
	//    public function find(Request $request): JsonResponse
	//    {
	//        $response = $this->findNFCsCUseCase->run(
	//            $request->toArray(),
	//            [
	//                'include' => $request->input('include')
	//            ]);
	//
	//        return HttpJson::OK($response);
	//    }

	/**
	 * Store a newly created resource in storage.
	 */
	public function store(StoreProductRequest $request)
	{
		//
	}


	/**
	 * Update the specified resource in storage.
	 */
	public function update(RegisterProductRequest $request, string $nfcId)
	{
		//
	}

	/**
	 * Remove the specified resource from storage.
	 */
	public function destroy(string $nfcId)
	{
		//
	}
}
