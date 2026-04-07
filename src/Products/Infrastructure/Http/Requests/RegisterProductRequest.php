<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

final class RegisterProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer'],
            'password' => ['required', 'string'],
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'error' => [
                    'code' => 'validation_error',
                    'context' => ['errors' => $validator->errors()->toArray()],
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
