<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class SubscribeToClassicPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'subscription_type_id' => ['required', 'integer', 'exists:subscription_types,id'],
            'billing_period'       => ['required', 'string', 'in:monthly,annual'],
            'payment_method_id'    => ['required', 'string'],
        ];
    }
}
