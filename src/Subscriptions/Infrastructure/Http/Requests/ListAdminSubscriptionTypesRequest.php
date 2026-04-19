<?php

declare(strict_types=1);

namespace Src\Subscriptions\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ListAdminSubscriptionTypesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'page'      => 'nullable|integer|min:1',
            'per_page'  => 'nullable|integer|min:1|max:100',
            'sort_by'   => 'nullable|string|in:name,slug,mode,base_price_cents,is_active,created_at',
            'sort_dir'  => 'nullable|string|in:asc,desc',
            'mode'      => 'nullable|string|in:classic,prepaid',
            'is_active' => 'nullable|boolean',
        ];
    }
}
