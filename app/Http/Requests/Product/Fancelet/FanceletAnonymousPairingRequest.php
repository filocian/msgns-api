<?php

declare(strict_types=1);

namespace App\Http\Requests\Product\Fancelet;

use App\Exceptions\Permissions\ActionNotAllowedException;
use App\Infrastructure\Services\Auth\AuthService;
use App\Models\User;
use App\Static\Permissions\StaticRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

final class FanceletAnonymousPairingRequest extends FormRequest
{
	public function authorize(Request $request, AuthService $authService): bool
	{
		$loggedUserId = $authService->id();
		$loggedUser = User::find($loggedUserId);

		if (!$loggedUser->hasAnyRole([StaticRoles::BACKOFFICE_ROLE, StaticRoles::DEV_ROLE, ])) {
			throw new ActionNotAllowedException();
		}

		return true;
	}

	public function rules(): array
	{
		return [
			'product_type_id' => 'required|integer|exists:product_types,id',
			'pairs' => 'required|array',
			'pairs.*' => 'required|array',
			'pairs.*.*' => 'required|numeric',
		];
	}
}
