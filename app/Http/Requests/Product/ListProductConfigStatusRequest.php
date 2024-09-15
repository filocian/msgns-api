<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Exceptions\Permissions\ActionNotAllowedException;
use App\Infrastructure\Services\Auth\AuthService;
use App\Models\User;
use App\Static\Permissions\StaticRoles;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ListProductConfigStatusRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 * @throws ActionNotAllowedException
	 */
	public function authorize(AuthService $authService): bool
	{
		$user = User::find($authService->id());

		if (!$user->hasAnyRole([StaticRoles::BACKOFFICE_ROLE, StaticRoles::DEV_ROLE, ])) {
			throw new ActionNotAllowedException();
		}

		return true;
	}

	/**
	 * Get the validation rules that apply to the request.
	 *
	 * @return array<string, ValidationRule|array|string>
	 */
	public function rules(): array
	{
		return [];
	}
}
