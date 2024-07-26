<?php

declare(strict_types=1);

namespace App\UseCases\Product\Assignment;

use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Contracts\UseCaseContract;
use App\Infrastructure\DTO\ProductDto;
use App\Models\Product;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final readonly class AssignToUserUC implements UseCaseContract
{
	public function __construct() {}

	/**
	 * UseCase: Assign a product to given user
	 *
	 * @param array{productId: int, email: string}|null $data
	 * @param array|null $opts
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 */
	public function run(mixed $data = null, ?array $opts = null): ProductDto
	{
		$productId = $data['productId'];
		$email = $data['email'];

		return $this->assignToUser($productId, $email);
	}

	/**
	 * Assign a product to given user
	 *
	 * @param int $productId
	 * @param string $email
	 * @return ProductDto
	 * @throws ProductNotFoundException
	 * @throws Exception
	 */
	private function assignToUser(int $productId, string $email): ProductDto
	{
		try {
			$user = User::where('email', $email)
				->firstOrFail();

			$product = Product::findById($productId,);

			$product->update([
				'user_id' => $user->id,
			]);

			$product->refresh();

			return ProductDto::fromModel($product);
		} catch (ModelNotFoundException $e) {
			throw new ProductNotFoundException();
		}
	}
}
