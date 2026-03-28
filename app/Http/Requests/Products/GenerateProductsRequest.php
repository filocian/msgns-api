<?php

declare(strict_types=1);

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;
use Src\Identity\Domain\Permissions\DomainPermissions;

/**
 * Format-only validation for the batch product generation endpoint.
 *
 * Business-rule validation (typeId existence, domain constraints) is
 * delegated to the handler layer.
 */
final class GenerateProductsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(DomainPermissions::PRODUCT_GENERATION) ?? false;
    }

    /**
     * @return array<string, string|list<string>>
     */
    public function rules(): array
    {
        return [
            'items'               => 'required|array|min:1',
            'items.*.typeId'      => 'required|integer|min:1',
            'items.*.quantity'    => 'required|integer|min:1',
            'items.*.size'        => 'nullable|string|max:255',
            'items.*.description' => 'nullable|string|max:1000',
        ];
    }
}
