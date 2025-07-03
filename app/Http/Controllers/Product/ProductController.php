<?php

declare(strict_types=1);

namespace App\Http\Controllers\Product;

use App\Exceptions\Product\ProductNotFoundException;
use App\Http\Contracts\Controller;
use App\Http\Contracts\HttpJson;
use App\Http\Requests\Product\ActivateProductRequest;
use App\Http\Requests\Product\AddProductBusinessRequest;
use App\Http\Requests\Product\ConfigureProductRequest;
use App\Http\Requests\Product\ListProductConfigStatusRequest;
use App\Http\Requests\Product\ProductListExportRequest;
use App\Http\Requests\Product\RegisterProductRequest;
use App\Http\Requests\Product\RenameProductRequest;
use App\Http\Requests\Product\SetProductConfigStatusRequest;
use App\Http\Requests\Product\SoftDeleteProductRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UsageStatsRequest;
use App\Http\Requests\Product\Whatsapp\GetWhatsappDataRequest;
use App\Http\Requests\Product\Whatsapp\RemoveWhatsappMessageRequest;
use App\Http\Requests\Product\Whatsapp\RemoveWhatsappPhoneRequest;
use App\Http\Requests\Product\Whatsapp\SaveInitialWhatsappDataRequest;
use App\Http\Requests\Product\Whatsapp\SaveWhatsappMessageRequest;
use App\Http\Requests\Product\Whatsapp\SaveWhatsappPhoneRequest;
use App\Http\Requests\Product\Whatsapp\SetDefaultWhatsappMessageRequest;
use App\Http\Requests\User\OnlyAdminRequest;
use App\UseCases\Product\Activation\ActivateUC;
use App\UseCases\Product\Activation\DeactivateUC;
use App\UseCases\Product\Assignment\AssignToCurrentUserUC;
use App\UseCases\Product\Assignment\AssignToUserUC;
use App\UseCases\Product\Businesses\AddBusinessUC;
use App\UseCases\Product\ConfigCloning\CloneFromCommonProductUC;
use App\UseCases\Product\ConfigCloning\FindCloneCompatibleProductsUC;
use App\UseCases\Product\Configuration\ConfigureUC;
use App\UseCases\Product\Configuration\ListConfigStatusUC;
use App\UseCases\Product\Configuration\RenameUC;
use App\UseCases\Product\Configuration\SetConfigStatusUC;
use App\UseCases\Product\Filtering\FindByCurrentUserUC;
use App\UseCases\Product\Filtering\FindByIdUC;
use App\UseCases\Product\Grouping\GetGroupCandidatesUC;
use App\UseCases\Product\Grouping\SetGroupUC;
use App\UseCases\Product\Listing\ProductListExportUC;
use App\UseCases\Product\Listing\ProductListUC;
use App\UseCases\Product\Redirect\ProductRedirectionUC;
use App\UseCases\Product\Registration\RegisterProductUC;
use App\UseCases\Product\SoftDelete\RestoreProductUC;
use App\UseCases\Product\SoftDelete\SoftDeleteProductUC;
use App\UseCases\Product\Stats\UsageOverviewUC;
use App\UseCases\Product\Whatsapp\AddMessageUC;
use App\UseCases\Product\Whatsapp\AddPhoneUC;
use App\UseCases\Product\Whatsapp\ListMessagesUC;
use App\UseCases\Product\Whatsapp\ListPhonesUC;
use App\UseCases\Product\Whatsapp\RemoveMessageUC;
use App\UseCases\Product\Whatsapp\RemovePhoneUC;
use App\UseCases\Product\Whatsapp\SetDefaultMessageUC;
use App\UseCases\Product\Whatsapp\SetInitialDataUC;
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
		private readonly ListConfigStatusUC $ListStatusUC,
		private readonly SetConfigStatusUC $SetConfigStatusUC,
		private readonly ListPhonesUC $ListPhonesUC,
		private readonly ListMessagesUC $ListMessagesUC,
		private readonly SetInitialDataUC $setInitialDataUC,
		private readonly AddPhoneUC $addPhoneUC,
		private readonly RemovePhoneUC $removePhoneUC,
		private readonly AddMessageUC $addMessageUC,
		private readonly RemoveMessageUC $removeMessageUC,
		private readonly SetDefaultMessageUC $setDefaultMessageUC,
		private readonly UsageOverviewUC $usageOverviewUC,
		private readonly FindCloneCompatibleProductsUC $findCloneCompatibleProductsUC,
		private readonly CloneFromCommonProductUC $cloneFromCommonProductUC,
		private readonly SoftDeleteProductUC $softDeleteProductUC,
		private readonly RestoreProductUC $restoreProductUC,
	) {}

	public function hello(): JsonResponse
	{
		return HttpJson::OK('hello nfc');
	}

	public function searchPlace(Request $request)
	{
		$apiKey = env('GOOGLE_PLACES_API_KEY');
		$placeName = $request->input('name');
		$response = Http::get(
			"https://maps.googleapis.com/maps/api/place/findplacefromtext/json?input=$placeName&inputtype=textquery&fields=place_id,type,photos,formatted_address,name,rating,opening_hours,geometry&key=$apiKey"
		);
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

	public function findById(Request $request, string $id, string|null $password = null): JsonResponse
	{
		$product = $this->FindProductByIdUC->run([
			'id' => (int) $id,
			'password' => $password,
		]);

		return HttpJson::OK($product->wrapped('product'));
	}

	public function findWithTrashedById(OnlyAdminRequest $request, string $id, string|null $password = null): JsonResponse
	{
		$product = $this->FindProductByIdUC->run([
			'id' => (int) $id,
			'password' => $password,
			'with_trashed' => true,
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
			'productId' => $id,
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
			'candidateId' => $candidateId,
		]);

		return HttpJson::OK($referenceProduct->wrapped('product'));
	}

	public function redirect(Request $request, int $id, string $password): JsonResponse
	{
		$browserLocales = $request->header('Accept-language', 'en-US,en;q=1');
		$browserLocale = preg_replace('/[\r\n\s]+/', '', $browserLocales); // Elimina saltos de línea y espacios
		$browserLocales = filter_var($browserLocales, FILTER_SANITIZE_FULL_SPECIAL_CHARS); // Sanitiza la cadena

		$productTarget = $this->ProductRedirectionUC->run([
			'id' => $id,
			'password' => $password,
			'browserLocales' => $browserLocales,
		]);

		return HttpJson::OK(['target_url' => $productTarget]);
	}

	public function getProductConfigStatusList(ListProductConfigStatusRequest $request): JsonResponse
	{
		$statusList = $this->ListStatusUC->run();

		return HttpJson::OK($statusList->wrapped('config_status_list'));
	}

	public function setProductConfigStatus(SetProductConfigStatusRequest $request, int $id): JsonResponse
	{
		$product = $this->SetConfigStatusUC->run([
			'productId' => $id,
			'status' => $request->input('status'),
		]);

		return HttpJson::OK($product->wrapped('product'));
	}

	/**
	 * Get product whatsapp phones
	 */
	public function getProductWhatsappPhones(GetWhatsappDataRequest $request, int $id): JsonResponse
	{
		$phones = $this->ListPhonesUC->run([
			'id' => $id,
		]);

		if (!$phones) {
			return HttpJson::OK(['phones' => []]);
		}

		return HttpJson::OK($phones->wrapped('phones'));
	}

	/**
	 * Get product whatsapp phones
	 */
	public function getProductWhatsappMessages(GetWhatsappDataRequest $request, int $id): JsonResponse
	{
		$messages = $this->ListMessagesUC->run([
			'id' => $id,
		]);

		if (!$messages) {
			return HttpJson::OK(['messages' => []]);
		}

		return HttpJson::OK($messages->wrapped('messages'));
	}

	/**
	 * Add product whatsapp phone
	 */
	public function addProductWhatsappPhone(SaveWhatsappPhoneRequest $request, int $id): JsonResponse
	{
		$phone = $this->addPhoneUC->run([
			'id' => $id,
			'phone_prefix' => $request->input('phone_prefix'),
			'phone_number' => $request->input('phone_number'),
		]);

		return HttpJson::OK($phone->wrapped('phone'));
	}

	/**
	 * Remove product whatsapp phone
	 */
	public function removeProductWhatsappPhone(RemoveWhatsappPhoneRequest $request, int $id): JsonResponse
	{
		$phone = $this->removePhoneUC->run([
			'phone_id' => $request->input('phone_id'),
		]);

		return HttpJson::OK($phone->wrapped('phone'));
	}

	/**
	 * Add product whatsapp message
	 */
	public function addProductWhatsappMessage(SaveWhatsappMessageRequest $request, int $id): JsonResponse
	{
		$message = $this->addMessageUC->run([
			'id' => $id,
			'phone_id' => $request->input('phone_id'),
			'message_locale_id' => $request->input('message_locale_id'),
			'message' => $request->input('message'),
		]);

		return HttpJson::OK($message->wrapped('message'));
	}

	/**
	 * Remove product whatsapp message
	 */
	public function removeProductWhatsappMessage(RemoveWhatsappMessageRequest $request, int $id): JsonResponse
	{
		$message = $this->removeMessageUC->run([
			'message_id' => $request->input('message_id'),
		]);

		return HttpJson::OK($message->wrapped('message'));
	}

	/**
	 * Set default product whatsapp message
	 */
	public function setDefaultProductWhatsappMessage(
		SetDefaultWhatsappMessageRequest $request,
		int $id,
		int $messageId
	): JsonResponse {
		$message = $this->setDefaultMessageUC->run([
			'product_id' => $id,
			'message_id' => $messageId,
		]);

		return HttpJson::OK($message->wrapped('message'));
	}

	/**
	 * Set product whatsapp initial data
	 */
	public function addProductWhatsappInitialData(SaveInitialWhatsappDataRequest $request, int $id): JsonResponse
	{
		$message = $this->setInitialDataUC->run([
			'id' => $id,
			'phone_prefix' => $request->input('phone_prefix'),
			'phone_number' => $request->input('phone_number'),
			'message_locale_id' => $request->input('message_locale_id'),
			'message' => $request->input('message'),
		]);

		return HttpJson::OK($message->wrapped('message'));
	}

	public function getProductUsageOverview(UsageStatsRequest $request, int $userId): JsonResponse
	{
		$usageOverview = $this->usageOverviewUC->run([
			'user_id' => $userId,
		]);

		return HttpJson::OK(['usage_overview' => $usageOverview]);
	}

	public function findCloneCompatibleProducts(int $product_id): JsonResponse
	{
		$candidates = $this->findCloneCompatibleProductsUC->run(['product_id' => $product_id]);

		return HttpJson::OK(['clone_candidates' => $candidates]);
	}

	public function cloneFromProduct(int $product_id, int $candidate_id): JsonResponse
	{
		$cloned = $this->cloneFromCommonProductUC->run([
			'product_id' => $product_id,
			'candidate_id' => $candidate_id,
		]);

		return HttpJson::OK(['cloned' => $cloned]);
	}

	public function softDeleteProduct(SoftDeleteProductRequest $request, int $id): JsonResponse
	{
		$deleted = $this->softDeleteProductUC->run([
			'product_id' => $id,
		]);

		return HttpJson::OK(['deleted' => $deleted]);
	}

	public function restoreProduct(OnlyAdminRequest $request, int $id): JsonResponse
	{
		$restored = $this->restoreProductUC->run([
			'product_id' => $id,
		]);

		return HttpJson::OK(['restored' => $restored]);
	}
}
