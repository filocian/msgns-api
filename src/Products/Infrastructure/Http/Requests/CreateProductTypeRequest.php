<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Format-only validation for creating a Product Type.
 *
 * Business-rule validation (uniqueness across the domain, usage gates) is
 * delegated to the handler layer. This request only checks that the incoming
 * payload is syntactically well-formed and within allowed string bounds.
 *
 * The `unique` rules below are format/schema guards: they prevent duplicate
 * `code` or `name` values at the DB level before the domain layer is invoked.
 */
final class CreateProductTypeRequest extends FormRequest
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
        return [
            'code'            => 'required|string|max:60|unique:product_types,code',
            'name'            => 'required|string|max:255|unique:product_types,name',
            'primary_model'   => 'required|string|max:255',
            'secondary_model' => 'nullable|string|max:255',
        ];
    }
}
