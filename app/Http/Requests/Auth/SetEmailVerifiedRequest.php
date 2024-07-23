<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Exceptions\Permissions\ActionNotAllowedException;
use App\Infrastructure\Services\Auth\AuthService;
use App\Models\User;
use App\Static\Permissions\StaticRoles;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

final class SetEmailVerifiedRequest extends FormRequest
{
	/**
	 * @throws ActionNotAllowedException
	 */
	public function authorize(Request $request, AuthService $authService): bool
	{
		$loggedUserId = $authService->id();
		$loggedUser = User::find($loggedUserId);

		try {
			$user = User::find((int) $request->route('id'));
		} catch (ModelNotFoundException $e) {
			throw new ModelNotFoundException();
		}

		if (!$loggedUser->hasAnyRole([
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
