<?php

namespace App\Http\Controllers;

use App\Http\Contracts\HttpJson;
use App\Http\Requests\StoreNFCRequest;
use App\Http\Requests\UpdateNFCRequest;
use App\Usecases\NFC\ActivateNFCUseCase;
use App\Usecases\NFC\FindNFCByIdCUseCase;
use App\Usecases\NFC\FindNFCsCUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NFCController extends Controller
{

    public function __construct(
        private readonly ActivateNFCUseCase  $activateNFCUseCase,
        private readonly FindNFCByIdCUseCase $findNFCByIdCUseCase,
        private readonly FindNFCsCUseCase    $findNFCsCUseCase,

    )
    {
    }

    public function hello()
    {
        return HttpJson::OK('hello nfc');
    }

    /**
     * Activate NFC. Set owner
     * @throws AuthenticationException
     */
    public function activate(Request $request, int $id): JsonResponse
    {
        $response = $this->activateNFCUseCase->run([
            'nfcId' => $id
        ]);
        return HttpJson::OK($response, Response::HTTP_CREATED);
    }


    /**
     * Display a listing of the resource.
     * @throws AuthenticationException
     */
    public function findById(int $id): JsonResponse
    {
        $response = $this->findNFCByIdCUseCase->run([
            'nfcId' => $id
        ]);

        return HttpJson::OK($response);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function find(Request $request): JsonResponse
    {
        $response = $this->findNFCsCUseCase->run($request->toArray());
        return HttpJson::OK($response);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNFCRequest $request)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateNFCRequest $request, string $nfcId)
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
