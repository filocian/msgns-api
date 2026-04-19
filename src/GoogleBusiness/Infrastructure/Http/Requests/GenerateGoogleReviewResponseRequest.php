<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Infrastructure\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Shared\Infrastructure\Http\ErrorResponseFactory;

final class GenerateGoogleReviewResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string|int>>
     */
    public function rules(): array
    {
        return [
            'product_id'  => ['required', 'integer', 'min:1'],
            'review_text' => ['required', 'string', 'min:1', 'max:10000'],
            'star_rating' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(ErrorResponseFactory::validationFailed($validator->errors()->toArray()));
    }
}
