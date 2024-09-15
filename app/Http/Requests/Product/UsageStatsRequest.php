<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Exceptions\Permissions\ActionNotAllowedException;
use App\Exceptions\Product\ProductNotFoundException;
use App\Exceptions\Product\ProductNotOwnedException;
use App\Infrastructure\Services\Auth\AuthService;
use App\Models\User;
use App\Static\Permissions\StaticRoles;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

final class UsageStatsRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 * @throws ActionNotAllowedException
	 * @throws ProductNotFoundException
	 * @throws ProductNotOwnedException
	 */
	public function authorize(Request $request, AuthService $authService): bool
	{
		$currentUserId = $authService->id();
		$userId = $request->route('user_id');

		if (!$userId || !$currentUserId) {
			throw new ActionNotAllowedException();
		}

		$currentUser = User::find($currentUserId);
		$isSuperUser = $currentUser->hasRole([StaticRoles::DEV_ROLE, StaticRoles::BACKOFFICE_ROLE]);

		if ($userId !== $currentUserId && !$isSuperUser) {
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
