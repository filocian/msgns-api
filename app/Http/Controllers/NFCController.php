<?php

namespace App\Http\Controllers;

use App\Exceptions\NFC\NFCNotFoundException;
use App\Exceptions\NFC\NFCNotOwnedException;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\NFC\ActivateNFCRequest;
use App\Http\Requests\NFC\StoreNFCRequest;
use App\Http\Requests\NFC\UpdateNFCRequest;
use App\Usecases\NFC\ActivateNFCUseCase;
use App\Usecases\NFC\FindNFCByIdCUseCase;
use App\Usecases\NFC\FindNFCsCUseCase;
use App\Usecases\NFC\LoggedUserNFCUseCase;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NFCController extends Controller
{

    public function __construct(
        private readonly ActivateNFCUseCase   $activateNFCUseCase,
        private readonly FindNFCByIdCUseCase  $findNFCByIdCUseCase,
        private readonly FindNFCsCUseCase     $findNFCsCUseCase,
        private readonly LoggedUserNFCUseCase $loggedUserNFCUseCase
    )
    {
    }

    public function hello()
    {
        return HttpJson::OK('hello nfc');
    }

    /**
     * Activate NFC. Set owner
     */
    public function activate(ActivateNFCRequest $request, int $id): JsonResponse
    {
        $response = $this->activateNFCUseCase->run([
            'nfcId' => $id,
            'password' => $request->input('password')
        ]);

        return HttpJson::OK($response, Response::HTTP_CREATED);
    }


    /**
     * Display a listing of the resource.
     * @throws AuthenticationException
     */
    public function findById(Request $request, string $id): JsonResponse
    {
        $response = $this->findNFCByIdCUseCase->run([
            'nfcId' => $id
        ], [
            'include' => $request->input('include', [])
        ]);

        return HttpJson::OK($response);
    }


    /**
     * Display a listing of current user's NFCs.
     * @throws NFCNotFoundException
     * @throws NFCNotOwnedException
     */
    public function mine(): JsonResponse
    {
        $response = $this->loggedUserNFCUseCase->run();
        return HttpJson::OK($response);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function find(Request $request): JsonResponse
    {
        $response = $this->findNFCsCUseCase->run(
            $request->toArray(),
            [
                'include' => $request->input('include')
            ]);

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
