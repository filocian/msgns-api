<?php

declare(strict_types=1);

namespace App\UseCases\Product\Filtering;

use App\DTO\PaginatedResponseDTO;
use App\DTO\ProductDto;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Services\Auth\AuthService;
use App\Services\ProductService;
use App\Traits\HasPaginationDTO;
use Exception;

final readonly class FindByCurrentUserUC implements UseCaseContract
{
	use HasPaginationDTO;

	public const string DATA_NAME = 'products';

	public function __construct(
		private AuthService $authService,
		private ProductService $productService
	) {}

	/**
	 * UseCase: Retrieves all products for the current user
	 *
	 * @param array|null $data
	 * @param array{perPage:int}|null $opts
	 * @return PaginatedResponseDTO
	 * @throws Exception
	 */
	public function run(mixed $data = null, ?array $opts = []): PaginatedResponseDTO
	{
		$userId = $this->authService->id();

		if ($userId === null) {
			throw new Exception('invalid_user');
		}

		$productsCollection = $this->productService->getProductsByUserId($userId, $this->resolveOptions($opts));

		return $this->buildPaginatedDTO(
			$productsCollection,
			ProductDto::fromModelCollection($productsCollection->collect()),
		);
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
