<?php

declare(strict_types=1);

namespace App\UseCases\Product\Redirect;

use App\Events\Product\ProductScannedEvent;
use App\Helpers\StringHelpers;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\Auth\AuthService;
use App\Infrastructure\Services\MixPanel\MPLogger;
use App\Models\Product;
use App\Models\ProductConfigurationStatus;
use App\Static\Product\Fancelet\FanceletFrontEndUrls;
use Exception;
use Illuminate\Http\Response;

final readonly class ProductRedirectionUC implements UseCaseContract
{
	public function __construct(
		private AuthService $authService,
		private WhatsappResolverUC $whatsappResolverUC,
		private MPLogger $mpLogger,
	) {}

	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param array{id: int, password: string, browserLocales: string}|null $data
	 * @param array|null $opts
	 * @return ProductDto | null
	 */
	public function run(mixed $data = null, ?array $opts = null): string|null
	{
		$productId = (int) $data['id'];
		$productPassword = $data['password'];
		$browserLocale = $data['browserLocales'];

		try {
			$product = Product::findByConfigPair($productId, 'password', $productPassword);
		} catch (Exception $e) {
			return $this->resolveNotFoundUrl();
		}

		return $this->resolveTargetUrl($product, $browserLocale);
	}

	public function updateProductUsage(Product $product): void
	{
		event(new ProductScannedEvent($product));
	}

	private function resolveTargetUrl(Product $product, string $browserLocale = null): string|Response
	{
		$loggedUserId = $this->authService->id();
		$productDto = ProductDto::fromModel($product);

		if ($this->isBraceletProduct($productDto)) {
			$this->updateProductUsage($product);

			return $this->resolveBraceletUrl($productDto);
		}

		//Producto desactivado -> disabled page
		if (!$productDto->active && !$this->isVirginProduct($productDto)) {
			return $this->resolveDisabledUrl($productDto);
		}

		//Producto virgen -> stepper
		if ($this->isVirginProduct($productDto)) {
			return $this->resolveStepperUrl($productDto);
		}

		//Producto incompleto -> stepper (si owner = loggedUserId) | incomplete info page
		if ($this->isMisconfiguredProduct($productDto) && !$this->canBypassStatusCheck($productDto)) {
			$this->mpLogger->warn('PRODUCT_REDIRECTION', 'PRODUCT MISCONFIGURED', 'product misconfigured redirection', [
				'product_id' => $productDto->id,
				'user_id' => $loggedUserId,
			]);

			return $this->resolveIncompleteUrl($productDto, $loggedUserId);
		}

		$target_url = $productDto->target_url;

		if ($productDto->model === 'whatsapp') {
			$whatsappUrl = $this->whatsappResolverUC->run([
				'productModel' => $product,
				'browserLocales' => $browserLocale,
			]);

			if (!$whatsappUrl) {
				$this->mpLogger->warn(
					'PRODUCT_REDIRECTION',
					'WHATSAPP PRODUCT MISCONFIGURED',
					'whatsapp product misconfigured redirection',
					[
						'product_id' => $productDto->id,
						'user_id' => $loggedUserId,
					]
				);

				return $this->resolveIncompleteUrl($productDto, $loggedUserId);
			}

			return response()->view('whatsapp.whatsapp-redirection', ['url' => $whatsappUrl]);
		}

		$this->updateProductUsage($product);

		return $target_url;
	}

	private function resolveStepperUrl(ProductDto $productDto): string
	{
		return env('FRONT_URL') . '/product/' . $productDto->id . '/register/' . $productDto->password;
	}

	private function resolveDisabledUrl(ProductDto $productDto): string
	{
		return env('FRONT_URL') . '/product/disabled';
	}

	private function resolveIncompleteUrl(ProductDto $productDto, int|null $userId): string
	{
		$obfuscatedOwnerEmail = StringHelpers::obfuscateEmail($productDto->user->email);
		if ($userId === null || $productDto->user->id !== $userId) {
			return env('FRONT_URL') . '/product/pending-configuration?email=' . $obfuscatedOwnerEmail;
		}

		return $this->resolveStepperUrl($productDto);
	}

	private function resolveNotFoundUrl(): string
	{
		return env('FRONT_URL') . '/product/not-found';
	}

	private function isVirginProduct(ProductDto $productDto): bool
	{
		$hasOwner = boolval($productDto->user);
		$hasTarget = boolval($productDto->target_url);
		$configStatus = $productDto->configuration_status ?? ProductConfigurationStatus::$STATUS_NOT_STARTED;

		return !$hasOwner && !$hasTarget && $configStatus === ProductConfigurationStatus::$STATUS_NOT_STARTED;
	}

	private function isMisconfiguredProduct(ProductDto $productDto): bool
	{
		$misconfiguredStatuses = [
			ProductConfigurationStatus::$STATUS_ASSIGNED,
			ProductConfigurationStatus::$STATUS_TARGET_SET,
			ProductConfigurationStatus::$STATUS_BUSINESS_SET,
		];
		$configStatus = $productDto->configuration_status ?? ProductConfigurationStatus::$STATUS_NOT_STARTED;

		return in_array($configStatus, $misconfiguredStatuses);
	}

	private function canBypassStatusCheck(ProductDto $productDto): bool
	{
		$hasOwner = boolval($productDto->user);
		$hasTarget = boolval($productDto->target_url);

		if ($productDto->model === 'whatsapp') {
			$hasTarget = $productDto->configuration_status === ProductConfigurationStatus::$STATUS_TARGET_SET;
		}

		return $hasOwner && $hasTarget;
	}

	private function isBraceletProduct(ProductDto $productDto): bool
	{
		return str_starts_with($productDto->type->code, 'B-');
	}

	private function resolveBraceletUrl(ProductDto $productDto): string
	{
		$productTypeCode = $productDto->type->code;
		$productTypeDefinition = substr($productTypeCode, 0, 4);
		return match ($productTypeDefinition) {
			'B-LO' => env(
				'FRONT_URL'
			) . FanceletFrontEndUrls::$LOB1 . '?id=' . $productDto->id . '&pwd=' . $productDto->password,
			'B-BI' => env(
				'FRONT_URL'
			) . FanceletFrontEndUrls::$BIB1 . '?id=' . $productDto->id . '&pwd=' . $productDto->password,
		};
		//		return env('APP_URL') . '/bracelet/test/' . $productDto->id;
	}
}
