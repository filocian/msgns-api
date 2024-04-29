<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

final class SignupRequest extends FormRequest
{
	public function rules(): array
	{
		return [
			'name' => 'required|string',
			'email' => 'required|string|unique:users,email',
			'password' => 'required|string:min:6|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
			'repeat_password' => 'required|string|same:password',
		];
	}
}
