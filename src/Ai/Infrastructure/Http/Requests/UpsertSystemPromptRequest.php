<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class UpsertSystemPromptRequest extends FormRequest
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
            'prompt_text' => ['required', 'string'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $text = $this->input('prompt_text');
            if (is_string($text) && str_word_count($text) > 1000) {
                $validator->errors()->add('prompt_text', 'The prompt text must not exceed 1000 words.');
            }
        });
    }
}
