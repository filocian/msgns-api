<?php

declare(strict_types=1);

namespace App\UseCases\Product\Filtering;

use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Models\Product;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class FindByIdUC implements UseCaseContract
{
	public function __construct() {}

	/**
	 * Retrieves a product by its ID
	 *
	 * @param array{id: int, password: string|null}|null $data
	 * @throws ProductNotFoundException|Exception
	 */
	public function run(mixed $data = null, ?array $opts = []): ProductDto
	{
		$productId = $data['id'];
		$productPassword = $data['password'];

		if ($productPassword) {
			return $this->findByIdAndPassword($productId, $productPassword);
		}

		return $this->findById($productId, $opts);
	}

	/**
	 * Retrieves a product by its ID
	 *
	 * @param int $id
	 * @param array|null $opts
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 * @throws Exception
	 */
	private function findById(int $id, ?array $opts = []): ProductDto
	{
		if (!isset($id)) {
			throw new Exception('invalid_nfc_id');
		}

		$exclude = $opts['exclude'] ?? [];

		try {
			$product = Product::findById($id);

			return ProductDto::fromModel($product, $exclude);
		} catch (ModelNotFoundException $e) {
			throw new ProductNotFoundException();
		}
	}

	private function findByIdAndPassword(int $id, string $password): ProductDto
	{
		if (!isset($id)) {
			throw new Exception('invalid_nfc_id');
		}

		$exclude = $opts['exclude'] ?? [];

		try {
			$product = Product::findByConfigPair($id, 'password', $password);

			return ProductDto::fromModel($product, $exclude);
		} catch (ModelNotFoundException $e) {
			throw new ProductNotFoundException();
		}
	}
}
