<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Format-only validation for updating a Product Type (partial update).
 *
 * All fields are optional — only the provided fields are validated and later
 * forwarded to the handler. The `unique` rules ignore the current record's own
 * row so that sending the same `code` or `name` back does not trigger a false
 * conflict.
 *
 * Business-rule validation (protected-field gate when the type is in use) is
 * enforced exclusively by the domain layer (`ProductType::applyUpdate()`).
 */
final class UpdateProductTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        /** @var int|string $id */
        $id = $this->route('id');

        return [
            'code'            => 'sometimes|string|max:60|unique:product_types,code,' . $id,
            'name'            => 'sometimes|string|max:255|unique:product_types,name,' . $id,
            'primary_model'   => 'sometimes|string|max:255',
            'secondary_model' => 'nullable|string|max:255',
        ];
    }
}
