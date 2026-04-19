<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Shared\Infrastructure\Http\ErrorResponseFactory;

final class AddBusinessInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:150'],
            'not_a_business' => ['nullable', 'boolean'],
            'types' => ['required', 'array'],
            'place_types' => ['nullable', 'array'],
            'size' => ['nullable', 'string', 'max:50'],
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(ErrorResponseFactory::validationFailed($validator->errors()->toArray()));
    }
}
