<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\Infrastructure\Services\Auth\AuthService;
use Illuminate\Foundation\Http\FormRequest;

final class LogoutRequest extends FormRequest
{
	/**
	 * Determine if the user is authorized to make this request.
	 */
	public function authorize(AuthService $authService): bool
	{
		return !(!$authService->user())
			 
		

		;
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
