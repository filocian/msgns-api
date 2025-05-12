<?php

declare(strict_types=1);

namespace App\Http\Requests\Product\Fancelet;

use App\Infrastructure\Services\Auth\AuthService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

final class FanceletRegisteredUserRequest extends FormRequest
{
	public function authorize(Request $request, AuthService $authService): bool
	{
		return boolval($authService->id());
	}

	public function rules(): array
	{
		return [];
	}
}
