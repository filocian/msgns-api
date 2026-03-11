<?php

declare(strict_types=1);

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Validator;

final class SignUpRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        Validator::extend('custom_password', function ($attribute, $value) {
            return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?:{}|_<=>-]).*$/', $value);
        }, 'Password must contain at least one lowercase letter, one uppercase letter, one number, and one special character.');
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'           => 'required|email|max:255',
            'name'            => 'required|string|min:2|max:255',
            'password'        => 'required|string|min:' . env('PASSWORD_MIN_LENGTH', 8) . '|custom_password',
            'repeat_password' => 'required|string|same:password',
        ];
    }
}
