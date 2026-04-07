<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Request validation for bulk email change endpoint.
 * Validates that updates contain unique user_ids and valid email formats.
 */
final class BulkChangeEmailRequest extends FormRequest
{
    public const MAX_BATCH_SIZE = 100;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'updates' => 'required|array|min:1|max:' . self::MAX_BATCH_SIZE,
            'updates.*.user_id' => 'required|integer|min:1',
            'updates.*.email' => 'required|string|email|max:255',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * Adds custom validation to check for duplicate user_ids in the request.
     *
     * @param \Illuminate\Validation\Validator $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $updates = $this->input('updates', []);

            if (!is_array($updates) || count($updates) === 0) {
                return;
            }

            $userIds = [];
            foreach ($updates as $index => $update) {
                if (!is_array($update) || !isset($update['user_id'])) {
                    continue;
                }
                $userId = (int) $update['user_id'];
                if (in_array($userId, $userIds, true)) {
                    $validator->errors()->add(
                        "updates.{$index}.user_id",
                        'Duplicate user_id in request. Each user can only appear once.'
                    );
                }
                $userIds[] = $userId;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'updates.required' => 'The updates field is required.',
            'updates.array' => 'The updates must be an array.',
            'updates.min' => 'At least one update is required.',
            'updates.max' => 'Maximum batch size is ' . self::MAX_BATCH_SIZE . ' updates.',
            'updates.*.user_id.required' => 'Each update must have a user_id.',
            'updates.*.user_id.integer' => 'Each user_id must be a positive integer.',
            'updates.*.email.required' => 'Each update must have an email.',
            'updates.*.email.email' => 'Each email must be a valid email address.',
        ];
    }

    /**
     * Get validated and normalized updates.
     *
     * Returns array mapping user_id => normalized_email
     *
     * @return array<int, string>
     */
    public function validatedUpdates(): array
    {
        /** @var array<int, array{user_id: int, email: string}> $updates */
        $updates = $this->validated('updates');

        $result = [];
        foreach ($updates as $update) {
            $userId = (int) $update['user_id'];
            $normalizedEmail = strtolower(trim($update['email']));
            $result[$userId] = $normalizedEmail;
        }

        return $result;
    }
}
