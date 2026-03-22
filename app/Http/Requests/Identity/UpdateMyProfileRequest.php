<?php

declare(strict_types=1);

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class UpdateMyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'name'           => 'sometimes|string|min:2|max:255',
            'phone'          => 'sometimes|nullable|string|max:50',
            'country'        => 'sometimes|nullable|string|max:100',
            'default_locale' => 'sometimes|nullable|string|in:en,es,de,fr,it,ca',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $fields = ['name', 'phone', 'country', 'default_locale'];
            $hasAtLeastOne = false;

            foreach ($fields as $field) {
                if ($this->has($field)) {
                    $hasAtLeastOne = true;
                    break;
                }
            }

            if (!$hasAtLeastOne) {
                $validator->errors()->add('general', 'At least one field must be provided.');
            }
        });
    }
}
