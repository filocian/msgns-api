<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class RequestEmailChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'new_email' => 'required|string|email|max:255',
            'password'  => 'required|string',
        ];
    }
}
