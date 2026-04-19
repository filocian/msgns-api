<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Shared\Infrastructure\Http\ErrorResponseFactory;

/**
 * Format-only validation for reporting a product usage event.
 *
 * Shape validation only: required fields, types, and string bounds.
 * Business-rule validation (product existence) is delegated to the handler.
 *
 * Overrides failedValidation() to return HTTP 422 (Unprocessable Entity)
 * in alignment with the v2 API contract (spec FR: invalid request → 422).
 */
final class ReportUsageRequest extends FormRequest
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
            'userId'      => ['required', 'integer'],
            'productName' => ['required', 'string', 'max:255'],
            // Strict ISO-8601 datetime: YYYY-MM-DDTHH:MM:SS with optional fractional seconds
            // and mandatory UTC offset (Z or ±HH:MM).
            'scannedAt'   => [
                'required',
                'string',
                'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/',
            ],
        ];
    }

    /**
     * Override the default (400) validation failure to return 422,
     * matching the v2 API contract documented in OpenAPI.
     */
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(ErrorResponseFactory::validationFailed($validator->errors()->toArray()));
    }
}
