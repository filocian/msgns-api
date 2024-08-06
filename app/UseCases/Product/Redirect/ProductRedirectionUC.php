<?php

declare(strict_types=1);

namespace App\UseCases\Product\Redirect;

use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\Auth\AuthService;
use App\Models\Product;
use App\Models\ProductConfigurationStatus;

final readonly class ProductRedirectionUC implements UseCaseContract
{
	public function __construct(
		private AuthService    $authService,
		private ProductUsageUC $productUsageUC
	)
	{
	}

	/**
	 * UseCase: Activate a product based on product id and its password
	 *
	 * @param array{id: int, password: string}|null $data
	 * @param array|null $opts
	 * @return ProductDto | null
	 */
	public function run(mixed $data = null, ?array $opts = null): string|null
	{
		$productId = (int)$data['id'];
		$productPassword = $data['password'];

		try {
			$product = Product::findByConfigPair($productId, 'password', $productPassword);
		} catch (\Exception $e) {
			return $this->resolveNotFoundUrl();
		}

		return $this->resolveTargetUrl($product);
	}

	public function updateProductUsage(Product $product): void
	{
		$this->productUsageUC->run([
			'productModel' => $product
		]);
	}

	private function resolveTargetUrl(Product $product): string
	{
		$loggedUserId = $this->authService->id();
		$productDto = ProductDto::fromModel($product);
		$hasOwner = boolval($productDto->user);
		$hasTarget = boolval($productDto->target_url);
		$configStatus = $productDto->configuration_status ?? ProductConfigurationStatus::$STATUS_NOT_STARTED;

		//Producto virgen -> stepper
		if (!$hasOwner && !$hasTarget && $configStatus == ProductConfigurationStatus::$STATUS_NOT_STARTED) {
			return $this->resolveStepperUrl($productDto);
		}

		//Producto desactivado -> disabled page
		if (!$productDto->active) {
			return $this->resolveDisabledUrl($productDto);
		}

		//Producto incompleto -> stepper (si owner = loggedUserId) | incomplete info page
		if ($hasOwner && $configStatus != ProductConfigurationStatus::$STATUS_COMPLETED) {
			return $this->resolveIncompleteUrl($productDto, $loggedUserId);
		}

		$this->updateProductUsage($product);

		return $productDto->target_url;
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
		if ($userId == null || $productDto->user->id != $userId) {
			return env('FRONT_URL') . '/product/pending-configuration';
		}

		return $this->resolveStepperUrl($productDto);
	}

	private function resolveNotFoundUrl(): string
	{
		return env('FRONT_URL') . '/product/not-found';
	}
}
