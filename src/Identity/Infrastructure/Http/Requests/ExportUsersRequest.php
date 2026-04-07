<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ExportUsersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'search'       => 'nullable|string|max:255',
            'active'       => 'nullable|boolean',
            'role'         => 'nullable|string|exists:roles,name',
            'created_from' => 'nullable|date_format:Y-m-d',
            'created_to'   => 'nullable|date_format:Y-m-d|after_or_equal:created_from',
        ];
    }
}
