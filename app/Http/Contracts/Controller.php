<?php

declare(strict_types=1);

namespace App\Http\Contracts;

use App\Http\OpenApi\Schemas;
use OpenApi\Attributes as OA;

/**
 * Base controller with global OpenAPI metadata.
 *
 * This abstract class provides shared OpenAPI 3.0 documentation attributes
 * that apply to all API v2 endpoints. The security scheme is defined here
 * so that all authenticated endpoints can reference it.
 */
#[OA\Info(
    version: '2.0.0',
    title: 'MSGNS API v2',
    description: 'API v2 for the MSGNS application. Provides endpoints for Identity management (authentication, user profile, administration) and Products (product types).'
)]
#[OA\Server(url: '/api/v2', description: 'API v2 base URL')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum',
    description: 'Laravel Sanctum Bearer Token authentication. Obtain a token via POST /identity/login or POST /identity/sign-up.'
)]
#[OA\Schema(schema: 'JsonEnvelope', type: 'object', properties: [
    new OA\Property(property: 'data', type: 'object'),
], required: ['data'])]
#[OA\Schema(schema: 'PaginatedMeta', type: 'object', properties: [
    new OA\Property(property: 'current_page', type: 'integer'),
    new OA\Property(property: 'per_page', type: 'integer'),
    new OA\Property(property: 'total', type: 'integer'),
    new OA\Property(property: 'last_page', type: 'integer'),
], required: ['current_page', 'per_page', 'total', 'last_page'])]
#[OA\Schema(schema: 'UserResource', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'email', type: 'string', format: 'email'),
    new OA\Property(property: 'phone', type: 'string', nullable: true),
    new OA\Property(property: 'country', type: 'string', nullable: true),
    new OA\Property(property: 'default_locale', type: 'string', nullable: true),
    new OA\Property(property: 'active', type: 'boolean'),
    new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
], required: ['id', 'name', 'email', 'active', 'created_at', 'updated_at'])]
#[OA\Schema(schema: 'RoleResource', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'guard_name', type: 'string'),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
], required: ['id', 'name', 'guard_name', 'created_at', 'updated_at'])]
#[OA\Schema(schema: 'PermissionResource', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'guard_name', type: 'string'),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
], required: ['id', 'name', 'guard_name', 'created_at', 'updated_at'])]
#[OA\Schema(schema: 'ProductTypeResource', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'code', type: 'string'),
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'primary_model', type: 'string'),
    new OA\Property(property: 'secondary_model', type: 'string', nullable: true),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
], required: ['id', 'code', 'name', 'primary_model', 'created_at', 'updated_at'])]
#[OA\Schema(schema: 'ProductResource', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'productTypeId', type: 'integer'),
    new OA\Property(property: 'userId', type: 'integer', nullable: true),
    new OA\Property(property: 'model', type: 'string'),
    new OA\Property(property: 'linkedToProductId', type: 'integer', nullable: true),
    new OA\Property(property: 'password', type: 'string'),
    new OA\Property(property: 'targetUrl', type: 'string', nullable: true),
    new OA\Property(property: 'usage', type: 'integer'),
    new OA\Property(property: 'name', type: 'string'),
    new OA\Property(property: 'description', type: 'string', nullable: true),
    new OA\Property(property: 'active', type: 'boolean'),
    new OA\Property(property: 'configurationStatus', type: 'string'),
    new OA\Property(property: 'assignedAt', type: 'string', format: 'date-time', nullable: true),
    new OA\Property(property: 'size', type: 'string', nullable: true),
    new OA\Property(property: 'createdAt', type: 'string', format: 'date-time'),
    new OA\Property(property: 'updatedAt', type: 'string', format: 'date-time'),
    new OA\Property(property: 'deletedAt', type: 'string', format: 'date-time', nullable: true),
], required: ['id', 'productTypeId', 'model', 'password', 'usage', 'name', 'active', 'configurationStatus', 'createdAt', 'updatedAt'])]
#[OA\Schema(schema: 'ProductEnvelope', type: 'object', properties: [
    new OA\Property(property: 'data', properties: [
        new OA\Property(property: 'product', ref: '#/components/schemas/ProductResource'),
    ], type: 'object'),
], required: ['data'])]
#[OA\Schema(schema: 'BulkOperationResult', type: 'object', properties: [
    new OA\Property(property: 'results', type: 'array', items: new OA\Items(type: 'object', properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'success', type: 'boolean'),
        new OA\Property(property: 'message', type: 'string', nullable: true),
    ])),
    new OA\Property(property: 'total', type: 'integer'),
    new OA\Property(property: 'succeeded', type: 'integer'),
    new OA\Property(property: 'failed', type: 'integer'),
], required: ['results', 'total', 'succeeded', 'failed'])]
#[OA\Schema(schema: 'DomainError', type: 'object', properties: [
    new OA\Property(property: 'error', type: 'object', properties: [
        new OA\Property(property: 'code', type: 'string'),
        new OA\Property(property: 'context', type: 'object'),
    ], required: ['code']),
], required: ['error'])]
#[OA\Schema(schema: 'LoginResponse', type: 'object', properties: [
    new OA\Property(property: 'data', type: 'object', properties: [
        new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
        new OA\Property(property: 'access_token', type: 'string'),
        new OA\Property(property: 'token_type', type: 'string'),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
    ]),
], required: ['data'])]
#[OA\Schema(schema: 'MessageResponse', type: 'object', properties: [
    new OA\Property(property: 'data', type: 'object', properties: [
        new OA\Property(property: 'message', type: 'string'),
    ]),
], required: ['data'])]
#[OA\Schema(schema: 'GenerationHistorySummaryItem', type: 'object', properties: [
    new OA\Property(property: 'type_code', type: 'string'),
    new OA\Property(property: 'type_name', type: 'string'),
    new OA\Property(property: 'quantity', type: 'integer'),
    new OA\Property(property: 'size', type: 'string', nullable: true),
    new OA\Property(property: 'description', type: 'string', nullable: true),
], required: ['type_code', 'type_name', 'quantity'])]
#[OA\Schema(schema: 'GenerationHistoryListItem', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'generated_at', type: 'string', format: 'date-time'),
    new OA\Property(property: 'total_count', type: 'integer'),
    new OA\Property(property: 'summary', type: 'array', items: new OA\Items(ref: '#/components/schemas/GenerationHistorySummaryItem')),
    new OA\Property(property: 'generated_by', nullable: true, properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
    ], type: 'object'),
], required: ['id', 'generated_at', 'total_count', 'summary'])]
abstract class Controller
{
    //
}
