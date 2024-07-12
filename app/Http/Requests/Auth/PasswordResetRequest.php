<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

final class PasswordResetRequest extends FormRequest
{
	protected function prepareForValidation()
	{
		Validator::extend('custom_password', function($attribute, $value, $parameters, $validator) {
			return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?:{}|_<=>-]).*$/', $value);
		}, 'Password must contain at least one lowercase letter, one uppercase letter, one number, and one special character.');
	}
	public function rules(): array
	{
		return [
			'token' => 'required|string',
			'password' => 'required|string:min:6|custom_password',
		];
	}
}
