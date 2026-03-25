<?php

declare(strict_types=1);

namespace App\Http\OpenApi;

/**
 * Schema constants for reusable OpenAPI components.
 *
 * These constants are used for $ref references in controller annotations.
 * Example: new OA\JsonContent(ref: '#/components/schemas/UserResource')
 */
class Schemas
{
    public const JSON_ENVELOPE = 'JsonEnvelope';
    public const PAGINATED_META = 'PaginatedMeta';
    public const USER_RESOURCE = 'UserResource';
    public const ROLE_RESOURCE = 'RoleResource';
    public const PERMISSION_RESOURCE = 'PermissionResource';
    public const PRODUCT_TYPE_RESOURCE = 'ProductTypeResource';
    public const BULK_OPERATION_RESULT = 'BulkOperationResult';
    public const DOMAIN_ERROR = 'DomainError';
    public const LOGIN_RESPONSE = 'LoginResponse';
    public const MESSAGE_RESPONSE = 'MessageResponse';
}
