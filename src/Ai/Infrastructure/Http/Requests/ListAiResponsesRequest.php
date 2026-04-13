<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Src\Ai\Domain\ValueObjects\AiProductType;
use Src\Ai\Domain\ValueObjects\AiResponseStatus;

final class ListAiResponsesRequest extends FormRequest
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
        $validProductTypes = implode(',', array_column(AiProductType::cases(), 'value'));

        return [
            'status'       => ['nullable', 'string', 'in:' . implode(',', [
                AiResponseStatus::PENDING,
                AiResponseStatus::APPROVED,
                AiResponseStatus::EDITED,
                AiResponseStatus::REJECTED,
                AiResponseStatus::APPLIED,
                AiResponseStatus::EXPIRED,
            ])],
            'product_type' => ['nullable', 'string', 'in:' . $validProductTypes],
            'page'         => ['nullable', 'integer', 'min:1'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
