<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class VerifyEmailRequest extends FormRequest
{
	public function rules(): array
	{
		return [
			'token' => 'required|string',
			'email' => 'required|string',
		];
	}
}
