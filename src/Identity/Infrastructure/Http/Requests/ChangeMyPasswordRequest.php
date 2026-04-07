<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ChangeMyPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|max:128|confirmed',
        ];
    }
}
