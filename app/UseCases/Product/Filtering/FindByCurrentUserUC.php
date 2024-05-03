<?php

declare(strict_types=1);

namespace App\UseCases\Product\Filtering;

use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\PaginatorDto;
use App\Infrastructure\DTO\ProductDto;
use App\Infrastructure\Services\Auth\AuthService;
use App\Infrastructure\Services\Product\ProductService;
use Exception;

final readonly class FindByCurrentUserUC implements UseCaseContract
{
	public function __construct(
		private AuthService $authService,
		private ProductService $productService
	) {}

	/**
	 * UseCase: Retrieves all products for the current user
	 *
	 * @param array|null $data
	 * @param array{perPage:int}|null $opts
	 * @return PaginatorDto<ProductDto>
	 * @throws Exception
	 */
	public function run(mixed $data = null, ?array $opts = []): PaginatorDto
	{
		$userId = $this->authService->id();

		if ($userId === null) {
			throw new Exception('invalid_user');
		}

		$productsCollection = $this->productService->getProductsByUserId($userId, $this->resolveOptions($opts));

		return PaginatorDto::fromPaginator($productsCollection, ProductDto::class);
	}

	/**
	 * Resolve UseCase Options
	 *
	 * @param array{perPage:int}|null $options
	 * @return array
	 */
	private function resolveOptions(?array $options): array
	{
		return array_merge([
			'perPage' => (int) env('DEFAULT_PAGINATION_LENGTH', 15),
		], $options);
	}
}
