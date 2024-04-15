<?php

namespace App\Http\Controllers;

use App\Exceptions\Product\ProductNotFoundException;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\UseCases\Product\UCActivateProduct;
use App\UseCases\Product\UCAssignToCurrentUser;
use App\UseCases\Product\UCAssignToUser;
use App\UseCases\Product\UCDeactivateProduct;
use App\UseCases\Product\UCFindProductByLoggedUser;
use App\UseCases\Product\UCFindProductById;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{

    public function __construct(
        private readonly UCActivateProduct         $UCActivateProduct,
        private readonly UCDeactivateProduct       $UCDeactivateProduct,
        private readonly UCAssignToUser            $UCAssignToUser,
        private readonly UCAssignToCurrentUser     $UCAssignToCurrentUser,
        private readonly UCFindProductById         $UCFindProductById,
        private readonly UCFindProductByLoggedUser $UCFindProductByLoggedUser
    )
    {
    }

    public function hello()
    {
        return HttpJson::OK('hello nfc');
    }

    public function activate(int $id): JsonResponse
    {
        $response = $this->UCActivateProduct->run([
            'id' => $id,
        ]);

        return HttpJson::OK($response, Response::HTTP_CREATED);
    }

    public function deactivate(int $id): JsonResponse
    {
        $response = $this->UCDeactivateProduct->run([
            'id' => $id,
        ]);

        return HttpJson::OK($response, Response::HTTP_CREATED);
    }

    public function assign(int $id, int $userId): JsonResponse
    {
        $response = $this->UCAssignToUser->run([
            'id' => $id,
        ]);

        return HttpJson::OK($response, Response::HTTP_CREATED);
    }

    public function assignToCurrentUser(int $id, string $password): JsonResponse
    {
        $response = $this->UCAssignToCurrentUser->run([
            'id' => $id,
            'password' => $password
        ]);

        return HttpJson::OK($response, Response::HTTP_CREATED);
    }

    public function findById(Request $request, int $id): JsonResponse
    {
        $response = $this->UCFindProductById->run([
            'id' => $id
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
        $response = $this->UCFindProductByLoggedUser->run();
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
    public function update(UpdateProductRequest $request, string $nfcId)
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
