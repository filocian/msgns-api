<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DeleteSystemPromptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $this->merge(['product_type' => $this->route('product_type')]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'product_type' => ['required', 'in:google_review,instagram_content'],
        ];
    }
}
