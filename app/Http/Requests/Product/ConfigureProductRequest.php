<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Exceptions\Permissions\ActionNotAllowedException;
use App\Exceptions\Product\ProductNotFoundException;
use App\Exceptions\Product\ProductNotOwnedException;
use App\Infrastructure\Services\Auth\AuthService;
use App\Models\Product;
use App\Models\User;
use App\Static\Permissions\StaticPermissions;
use App\Static\Permissions\StaticRoles;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

final class ConfigureProductRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 * @throws ActionNotAllowedException
	 * @throws ProductNotFoundException
	 * @throws ProductNotOwnedException
	 */
	public function authorize(Request $request, AuthService $authService): bool
	{
		$userId = $authService->id();
		$user = User::find($userId);
		$productId = (int) $request->route('id');

		try {
			$product = Product::findById($productId);
		} catch (ModelNotFoundException $e) {
			throw new ProductNotFoundException();
		}

		$isSuperUser = $user->hasRole([
			StaticRoles::DEV_ROLE,
			StaticRoles::BACKOFFICE_ROLE
		]);

		$isOwner = $user->id == $product->user_id;

		if (!$isSuperUser && !$isOwner) {
			throw new ProductNotOwnedException();
		}

		$userHasPermissions = $user->hasAllPermissions([
			StaticPermissions::SINGLE_PRODUCT_CONFIGURATION,
		]);

		if (!$userHasPermissions) {
			throw new ActionNotAllowedException();
		}

		return true;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
	 */
	public function rules(): array
	{
		return [
			'configuration' => 'required|array',
		];
	}
}
