<?php

declare(strict_types=1);

namespace App\Infrastructure\Services\Product;

use App\Infrastructure\DTO\CollectionDto;
use App\Infrastructure\DTO\ProductBusinessDto;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\DTO\ProductTypeDto;
use App\Models\Product;
use App\Models\ProductBusiness;
use App\Models\ProductConfigurationStatus;
use App\Models\ProductType;
use App\Models\Whatsapp\WhatsappMessage;
use App\Models\Whatsapp\WhatsappPhone;
use App\UseCases\Product\Whatsapp\ListMessagesUC;
use JetBrains\PhpStorm\ExpectedValues;

final readonly class CloneProductService
{
	public function __construct(
		private ListMessagesUC $listMessagesUC,
	) {}

	private function hasValidStatuses(Product|ProductDto $productCandidate): bool
	{
		$validStatuses = [
			ProductConfigurationStatus::$STATUS_TARGET_SET,
			ProductConfigurationStatus::$STATUS_BUSINESS_SET,
			ProductConfigurationStatus::$STATUS_COMPLETED,
		];

		return in_array($productCandidate->configuration_status, $validStatuses);
	}

	private function hasValidTypology(Product|ProductDto $currentProduct, Product|ProductDto $productCandidate): bool
	{
		$allowedModels = ['google', 'instagram', 'facebook', 'whatsapp', 'youtube', 'info', 'tiktok'];
		$currentProductModel = $currentProduct->model;
		$candidateModel = $productCandidate->model;
		$isAllowedModel = in_array($currentProduct->model, $allowedModels) && in_array($candidateModel, $allowedModels);
		$isValidType = true;
		$isValidModel = $currentProductModel === $candidateModel;

		if ($productCandidate instanceof ProductDto) {
			$candidateTypeId = $productCandidate->type->id;
			$candidateType = $productCandidate->type;
		} else {
			$candidateTypeId = $productCandidate->product_type_id;
			$candidateType = ProductTypeDto::fromModel(ProductType::findById($candidateTypeId));
		}

		if ($currentProduct instanceof ProductDto) {
			$currentProductTypeId = $currentProduct->type->id;
			$currentProductType = $currentProduct->type;
		} else {
			$currentProductTypeId = $currentProduct->product_type_id;
			$currentProductType = ProductTypeDto::fromModel(ProductType::findById($currentProductTypeId));
		}

		if ($currentProductType->secondary_model || $candidateType->secondary_model) {
			$isValidType = $candidateTypeId == $currentProductTypeId;
		}

		return $isAllowedModel && ($isValidModel && $isValidType);
	}

	private function getProductData(ProductDto $productCandidate): array
	{
		return [
			'target_url' => $productCandidate->target_url,
			'name' => '(copy) ' . $productCandidate->name,
		];
	}

	private function getProductBusinessData(ProductDto $productCandidate): ?ProductBusinessDto
	{
		return ProductBusiness::findByProductId($productCandidate->id);
	}

	private function getProductWhatsappData(ProductDto $productCandidate): ?CollectionDto
	{
		return $this->listMessagesUC->run(['id' => $productCandidate->id]);
	}

	private function copyProductData(ProductDto $currentProduct, ProductDto $productCandidate): ProductDto|null
	{
		$productData = $this->getProductData($productCandidate);
		$clonedProduct = Product::findById($currentProduct->id);
		$data = [
			'name' => $productData['name'],
			'target_url' => $productData['target_url'],
			'configuration_status' => ProductConfigurationStatus::$STATUS_TARGET_SET,
		];

		if ($this->copyBusinessData($currentProduct, $productCandidate)) {
			$data['configuration_status'] = ProductConfigurationStatus::$STATUS_BUSINESS_SET;
		}

		$clonedProduct->update($data);
		$clonedProduct->refresh();

		return ProductDto::fromModel($clonedProduct);
	}

	private function copyWhatsappData(ProductDto $currentProduct, ProductDto $productCandidate): bool
	{
		$messages = $this->getProductWhatsappData($productCandidate);

		if (!$messages) {
			return false;
		}

		$messages->data->each(function ($message) use ($currentProduct) {
			$phone = WhatsappPhone::query()->firstOrCreate([
				'product_id' => $currentProduct->id,
				'phone' => $message->phone->phone,
				'prefix' => $message->phone->prefix,
			]);

			$message = WhatsappMessage::query()->firstOrCreate([
				'product_id' => $currentProduct->id,
				'phone_id' => $phone->id,
				'message' => $message->message,
				'default' => $message->default,
				'locale_id' => $message->locale->id,
			]);
		});

		return true;
	}

	private function copyBusinessData(ProductDto $currentProduct, ProductDto $productCandidate): bool
	{
		$businessData = $this->getProductBusinessData($productCandidate);

		if (!$businessData) {
			return false;
		}

		ProductBusiness::query()->firstOrCreate([
			'product_id' => $currentProduct->id,
			'user_id' => $currentProduct->user->id,
			'not_a_business' => $businessData->not_a_business,
			'name' => $businessData->name,
			'types' => $businessData->types,
			'size' => $businessData->size,
			'place_types' => $businessData->place_types,
		]);

		return true;
	}

	public function isCompatible(Product|ProductDto $currentProduct, Product|ProductDto $productCandidate): bool
	{
		return $this->hasValidStatuses($productCandidate) && $this->hasValidTypology($currentProduct, $productCandidate);
	}

	/**
	 * cloneProduct: assuming you have a product with status assigned, and another of the same type fully configured,
	 * this function copies all the product configuration to the assigned one: product data, whatsapp data, and business data
	 *
	 * @param ProductDto $currentProduct
	 * @param ProductDto $productCandidate
	 * @return ProductDto|null
	 */
	public function cloneProduct(ProductDto $currentProduct, ProductDto $productCandidate): ProductDto|null
	{
		if (!$this->isCompatible($currentProduct, $productCandidate)) {
			return null;
		}

		if ($currentProduct->model === 'whatsapp') {
			$this->copyWhatsappData($currentProduct, $productCandidate);
		}

		return $this->copyProductData($currentProduct, $productCandidate);
	}

	/**
	 * copyPartialProductData: assuming you have a product with status assigned, and another of the same type fully configured,
	 * this function copies any of the product configurations to the assigned one: product data, whatsapp data, or business data
	 *
	 * @param ProductDto $currentProduct
	 * @param ProductDto $productCandidate
	 * @param string $mode
	 * @return ProductDto|null
	 */
	public function copyPartialProductData(
		ProductDto $currentProduct,
		ProductDto $productCandidate,
		#[ExpectedValues(['product_data', 'business_data', 'whatsapp_data'])]
		string $mode
	): ProductDto|null {
		if ($mode === 'business_data') {
			$this->copyBusinessData($currentProduct, $productCandidate);

			return ProductDto::fromModel(Product::findById($currentProduct->id));
		}

		if ($mode === 'whatsapp_data') {
			$this > $this->copyWhatsappData($currentProduct, $productCandidate);

			return ProductDto::fromModel(Product::findById($currentProduct->id));
		}

		if ($mode === 'product_data') {
			return $this->copyProductData($currentProduct, $productCandidate);
		}

		return $currentProduct;
	}
}
