<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Exceptions\Product\ProductNotFoundException;
use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Product\ActivateProductRequest;
use App\Http\Requests\Product\AddProductBusinessRequest;
use App\Http\Requests\Product\ConfigureProductRequest;
use App\Http\Requests\Product\ProductListExportRequest;
use App\Http\Requests\Product\RegisterProductRequest;
use App\Http\Requests\Product\RenameProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\UseCases\Product\Activation\ActivateUC;
use App\UseCases\Product\Activation\DeactivateUC;
use App\UseCases\Product\Assignment\AssignToCurrentUserUC;
use App\UseCases\Product\Assignment\AssignToUserUC;
use App\UseCases\Product\Businesses\AddBusinessUC;
use App\UseCases\Product\Configuration\ConfigureUC;
use App\UseCases\Product\Configuration\RenameUC;
use App\UseCases\Product\Filtering\FindByCurrentUserUC;
use App\UseCases\Product\Filtering\FindByIdUC;
use App\UseCases\Product\Grouping\GetGroupCandidatesUC;
use App\UseCases\Product\Grouping\SetGroupUC;
use App\UseCases\Product\Listing\ProductListExportUC;
use App\UseCases\Product\Listing\ProductListUC;
use App\UseCases\Product\Redirect\ProductRedirectionUC;
use App\UseCases\Product\Registration\RegisterProductUC;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;

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
		private readonly RegisterProductUC $RegisterProductUC,
		private readonly ProductListUC $productListUC,
		private readonly ProductListExportUC $ProductListExportUC,
		private readonly AddBusinessUC $AddBusinessUC,
		private readonly GetGroupCandidatesUC $GetGroupCandidatesUC,
		private readonly SetGroupUC $SetGroupUC,
		private readonly ProductRedirectionUC $ProductRedirectionUC,
	) {}

	public function hello()
	{
		return HttpJson::OK('hello nfc');
	}

	public function searchPlace(Request $request){
		$apiKey = env('GOOGLE_PLACES_API_KEY');
		$placeName = $request->input('name');
		$response = Http::get("https://maps.googleapis.com/maps/api/place/findplacefromtext/json?input=$placeName&inputtype=textquery&fields=place_id,type,photos,formatted_address,name,rating,opening_hours,geometry&key=$apiKey");
		return $response->json();
	}

	public function list(Request $request): JsonResponse
	{
		$products = $this->productListUC->run($request->all(), $request->all());
		return HttpJson::OK($products->wrapped('products'));
	}

	public function productListExport(ProductListExportRequest $request): JsonResponse
	{
		ini_set('memory_limit', '600M');
//		ini_set('max_execution_time', '300');

		$products = $this->ProductListExportUC->run($request->all(), $request->all());

		return HttpJson::OK(['products' => $products]);
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
	public function mine(Request $request): JsonResponse
	{
		$products = $this->FindProductByLoggedUserUC->run($request->all(), $request->all());
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
			'target_url' => $request->input('target_url'),
		]);

		return HttpJson::OK($product->wrapped('product'));
	}

	/**
	 * Add business information for a registered product
	 */
	public function addBusiness(AddProductBusinessRequest $request, int $productId): JsonResponse
	{
		$product = $this->AddBusinessUC->run([
			'productId' => $productId,
			'userId' => $request->input('user_id'),
			'notBusiness' => $request->input('not_a_business'),
			'name' => $request->input('name'),
			'types' => $request->input('types'),
			'size' => $request->input('size'),
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

	/**
	 * Get product group candidates
	 */
	public function getGroupCandidates(int $id): JsonResponse
	{
		$candidates = $this->GetGroupCandidatesUC->run([
			'productId' => $id
		]);

		return HttpJson::OK($candidates->wrapped('candidates'));
	}

	/**
	 * Set product group
	 */
	public function setProductGroup(int $referenceId, int $candidateId): JsonResponse
	{
		$referenceProduct = $this->SetGroupUC->run([
			'referenceId' => $referenceId,
			'candidateId' => $candidateId
		]);

		return HttpJson::OK($referenceProduct->wrapped('product'));
	}

	public function redirect(int $id, string $password): JsonResponse
	{
		$productTarget = $this->ProductRedirectionUC->run([
			'id' => $id,
			'password' => $password
		]);



		return HttpJson::OK(['target_url' => $productTarget]);
	}
}
