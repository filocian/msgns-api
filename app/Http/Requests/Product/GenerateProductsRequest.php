<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Exceptions\Permissions\ActionNotAllowedException;
use App\Infrastructure\Services\Auth\AuthService;
use App\Models\User;
use App\Static\Permissions\StaticPermissions;
use Illuminate\Foundation\Http\FormRequest;

final class GenerateProductsRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 * @throws ActionNotAllowedException
	 */
	public function authorize(AuthService $authService): bool
	{
		$user = User::find($authService->id());

		if (!$user->hasPermissionto(StaticPermissions::PRODUCT_GENERATION)) {
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
			'types' => 'required|array',
			'types.*' => 'required|array',
			'types.*.typeId' => 'required|integer|min:1',
			'types.*.quantity' => 'required|integer|min:1',
			'types.*.size' => 'sometimes|string',
		];
	}
}
