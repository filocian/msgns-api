<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AdminSetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'password' => 'required|string|min:8|max:128',
        ];
    }
}
