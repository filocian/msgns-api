<?php

declare(strict_types=1);

namespace Src\Subscriptions\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateSubscriptionTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        /** @var int|string $id */
        $id = $this->route('id');

        return [
            'name'                    => 'required|string|max:100|unique:subscription_types,name,' . $id,
            'description'             => 'nullable|string',
            'permission_name'         => 'required|string|max:100|unique:subscription_types,permission_name,' . $id,
            'google_review_limit'     => 'required|integer|min:0',
            'instagram_content_limit' => 'required|integer|min:0',
            'stripe_product_id'       => ['prohibited'],
            'mode'                    => ['prohibited'],
            'base_price_cents'        => ['prohibited'],
            'billing_periods'         => ['prohibited'],
        ];
    }
}
