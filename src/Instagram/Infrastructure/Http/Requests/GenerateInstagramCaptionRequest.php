<?php

declare(strict_types=1);

namespace Src\Instagram\Infrastructure\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

final class GenerateInstagramCaptionRequest extends FormRequest
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
            'product_id'      => ['required', 'integer', 'exists:products,id'],
            'image_base64'    => ['nullable', 'string', 'required_with:image_mime_type'],
            'image_mime_type' => ['nullable', 'required_with:image_base64', 'string', 'in:image/jpeg,image/png,image/webp'],
            'context'         => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'error' => [
                    'code'    => 'validation_error',
                    'context' => ['errors' => $validator->errors()->toArray()],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
