<?php

declare(strict_types=1);

namespace Src\Places\Infrastructure\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Src\Shared\Infrastructure\Http\ErrorResponseFactory;

final class SearchPlaceRequest extends FormRequest
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
			'name' => ['required', 'string', 'min:2', 'max:255'],
		];
	}

	protected function failedValidation(Validator $validator): never
	{
		throw new HttpResponseException(ErrorResponseFactory::validationFailed($validator->errors()->toArray()));
	}
}
