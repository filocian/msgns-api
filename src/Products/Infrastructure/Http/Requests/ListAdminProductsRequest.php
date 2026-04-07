<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class ListAdminProductsRequest extends FormRequest
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
            'sort_by' => ['sometimes', 'string', 'in:name,usage,active,configuration_status,model,assigned_at,created_at,product_type_code'],
            'sort_dir' => ['sometimes', 'string', 'in:asc,desc'],
            'timezone' => ['sometimes', 'nullable', 'string', 'timezone:all'],
            'assigned_at_from' => ['sometimes', 'nullable', 'string'],
            'assigned_at_to' => ['sometimes', 'nullable', 'string', 'after_or_equal:assigned_at_from'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
