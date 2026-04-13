<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class EditAiResponseRequest extends FormRequest
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
            'edited_content' => ['required', 'string', 'min:1', 'max:10000'],
        ];
    }
}
