<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Exceptions\Permissions\ActionNotAllowedException;
use App\Exceptions\Product\ProductAlreadyRegistered;
use App\Exceptions\Product\ProductNotFoundException;
use App\Infrastructure\Services\Auth\AuthService;
use App\Models\Product;
use App\Models\User;
use App\Static\Permissions\StaticRoles;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

final class AssignToUserRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 * @throws ActionNotAllowedException
	 * @throws ProductNotFoundException
	 * @throws ProductAlreadyRegistered
	 */
	public function authorize(Request $request, AuthService $authService): bool
	{
		$loggedUserId = $authService->id();
		$loggedUser = User::find($loggedUserId);
		$productId = (int) $request->route('id');

		try {
			$user = User::where('email', $request->input('email'));
			$product = Product::findById($productId);
		} catch (ModelNotFoundException $e) {
			throw new ProductNotFoundException();
		}

		if (!$loggedUser->hasAnyRole([StaticRoles::BACKOFFICE_ROLE, StaticRoles::DEV_ROLE, ])) {
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
		return [];
	}
}
