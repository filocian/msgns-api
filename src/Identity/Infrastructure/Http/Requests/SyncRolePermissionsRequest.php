<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SyncRolePermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            // 'present' ensures the key exists even if the array is empty [].
            // 'required' would reject [] — but empty array is valid for full-replace (removes all permissions).
            'permissions'   => ['present', 'array'],
            'permissions.*' => ['string'],
        ];
    }
}
