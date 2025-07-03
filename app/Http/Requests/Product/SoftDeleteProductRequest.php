<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Exceptions\Permissions\ActionNotAllowedException;
use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Services\Auth\AuthService;
use App\Models\Product;
use App\Models\User;
use App\Static\Permissions\StaticRoles;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

final class SoftDeleteProductRequest extends FormRequest
{
	/**
	 * @throws ActionNotAllowedException|ProductNotFoundException
	 */
	public function authorize(Request $request, AuthService $authService): bool
	{
		$loggedUserId = $authService->id();
		$loggedUser = User::find($loggedUserId);
		$productId = (int) $request->route('id');

		try {
			$product = Product::findById($productId);
		} catch (ModelNotFoundException $e) {
			throw new ProductNotFoundException();
		}

		try {
			$user = User::find((int) $loggedUserId);
		} catch (ModelNotFoundException $e) {
			throw new ModelNotFoundException();
		}

		if ($user->id !== $loggedUserId && !$loggedUser->hasAnyRole([
			StaticRoles::BACKOFFICE_ROLE,
			StaticRoles::DEV_ROLE,
		])) {
			throw new ActionNotAllowedException();
		}

		return true;
	}

	public function rules(): array
	{
		return [];
	}
}
