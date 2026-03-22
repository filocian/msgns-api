<?php

declare(strict_types=1);

namespace App\Http\Requests\Identity;

use Illuminate\Foundation\Http\FormRequest;
use Src\Identity\Domain\ValueObjects\RbacCatalog;

/**
 * Request validation for bulk role assignment endpoint.
 */
final class BulkAssignRolesRequest extends FormRequest
{
    public const MAX_BATCH_SIZE = 100;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        $validRoles = implode(',', RbacCatalog::roleNames());

        return [
            'user_ids' => 'required|array|min:1|max:' . self::MAX_BATCH_SIZE,
            'user_ids.*' => 'required|integer|min:1|distinct',
            'roles' => 'present|array',
            'roles.*' => 'string|in:' . $validRoles,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_ids.required' => 'The user_ids field is required.',
            'user_ids.array' => 'The user_ids must be an array.',
            'user_ids.min' => 'At least one user ID is required.',
            'user_ids.max' => 'Maximum batch size is ' . self::MAX_BATCH_SIZE . ' users.',
            'user_ids.*.integer' => 'Each user ID must be a positive integer.',
            'user_ids.*.distinct' => 'Duplicate user IDs are not allowed.',
            'roles.required' => 'The roles field is required.',
            'roles.array' => 'The roles must be an array.',
            'roles.*.in' => 'Invalid role specified. Valid roles: ' . implode(', ', RbacCatalog::roleNames()),
        ];
    }

    /**
     * Get validated user IDs.
     *
     * @return array<int>
     */
    public function validatedUserIds(): array
    {
        /** @var array<int> $ids */
        $ids = $this->validated('user_ids');
        return $ids;
    }

    /**
     * Get validated roles.
     *
     * @return array<string>
     */
    public function validatedRoles(): array
    {
        /** @var array<string> $roles */
        $roles = $this->validated('roles');
        return $roles;
    }
}
