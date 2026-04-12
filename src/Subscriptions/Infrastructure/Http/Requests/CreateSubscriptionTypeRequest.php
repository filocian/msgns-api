<?php

declare(strict_types=1);

namespace Src\Subscriptions\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateSubscriptionTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name'                    => 'required|string|max:100|unique:subscription_types,name',
            'description'             => 'nullable|string',
            'mode'                    => 'required|string|in:classic,prepaid',
            'billing_periods'         => 'nullable|array',
            'billing_periods.*'       => 'string|in:monthly,annual',
            'base_price_cents'        => 'required|integer|min:0',
            'permission_name'         => 'required|string|max:100|unique:subscription_types,permission_name',
            'google_review_limit'     => 'required|integer|min:0',
            'instagram_content_limit' => 'required|integer|min:0',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'billing_periods.required_if' => 'The billing periods field is required when mode is classic.',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $v): void {
            $mode            = $this->input('mode');
            $billingPeriods  = $this->input('billing_periods');

            if ($mode === 'classic' && (empty($billingPeriods) || !is_array($billingPeriods))) {
                $v->errors()->add('billing_periods', 'The billing periods field is required when mode is classic.');
            }

            if ($mode === 'prepaid' && !empty($billingPeriods)) {
                $v->errors()->add('billing_periods', 'Billing periods must be null or absent when mode is prepaid.');
            }
        });
    }
}
