<?php

declare(strict_types=1);

namespace App\UseCases\Product\Registration;

use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\Auth\AuthService;
use App\Infrastructure\Services\Product\ProductService;
use App\Models\Product;
use App\Models\ProductConfigurationStatus;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class RegisterProductUC implements UseCaseContract
{
	public function __construct(
		private AuthService $authService,
		private ProductService $productService,
	) {}

	/**
	 * UseCase: Assign a product to current user and activates it
	 *
	 * @param array{id: int, password: string}|null $data
	 * @param array|null $opts
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 */
	public function run(mixed $data = null, ?array $opts = null): ProductDto
	{
		$productId = $data['id'];
		$password = $data['password'];

		return $this->assignProductToCurrentUser($productId, $password);
	}

	/**
	 * Assign a product to current user and activates it
	 *
	 * @param int $productId
	 * @param string $password
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 * @throws Exception
	 */
	private function assignProductToCurrentUser(int $productId, string $password): ProductDto
	{
		$userId = $this->authService->id();

		try {
			$product = Product::findByConfigPair($productId, 'password', $password);

			$configStatus = $this->productService
				->resolveConfigurationStatus($product, ProductConfigurationStatus::$STATUS_ASSIGNED);

			$product->update([
				'user_id' => $userId,
				'active' => true,
				'configuration_status' => $configStatus,
				'assigned_at' => Carbon::now()->toDateTimeString(),
			]);

			$product->refresh();

			return ProductDto::fromModel($product);
		} catch (ModelNotFoundException $e) {
			throw new ProductNotFoundException();
		}
	}
}
