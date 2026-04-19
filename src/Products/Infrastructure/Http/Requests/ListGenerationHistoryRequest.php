<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Shared\Infrastructure\Http\ErrorResponseFactory;

final class ListGenerationHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(DomainPermissions::PRODUCT_GENERATION) ?? false;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'timezone' => ['sometimes', 'string', 'timezone:all'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(ErrorResponseFactory::validationFailed($validator->errors()->toArray()));
    }
}
