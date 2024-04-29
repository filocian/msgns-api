<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Exceptions\Permissions\ActionNotAllowedException;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Static\Permissions\StaticPermissions;
use Illuminate\Foundation\Http\FormRequest;

final class RegisterProductRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 * @throws ActionNotAllowedException
	 */
	public function authorize(AuthService $authService): bool
	{
		$user = User::find($authService->id());

		if (!$user->hasAllPermissions([
			StaticPermissions::SINGLE_PRODUCT_ASSIGNMENT,
			StaticPermissions::SINGLE_PRODUCT_ACTIVATION,
		])) {
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
