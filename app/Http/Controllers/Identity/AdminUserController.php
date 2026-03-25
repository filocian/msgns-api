<?php

declare(strict_types=1);

namespace App\Http\Controllers\Identity;

use App\Http\Contracts\Controller;
use App\Http\OpenApi\Schemas as OpenApiSchemas;
use App\Http\Requests\Identity\AdminSetPasswordRequest;
use App\Http\Requests\Identity\AdminUpdateUserRequest;
use App\Http\Requests\Identity\BulkActivationRequest;
use App\Http\Requests\Identity\BulkAssignRolesRequest;
use App\Http\Requests\Identity\BulkChangeEmailRequest;
use App\Http\Requests\Identity\BulkPasswordResetRequest;
use App\Http\Requests\Identity\BulkVerifyEmailRequest;
use App\Http\Requests\Identity\ExportUsersRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\AdminActivateUser\AdminActivateUserCommand;
use Src\Identity\Application\Commands\AdminDeactivateUser\AdminDeactivateUserCommand;
use Src\Identity\Application\Commands\AdminSetEmailVerified\AdminSetEmailVerifiedCommand;
use Src\Identity\Application\Commands\AdminSetPassword\AdminSetPasswordCommand;
use Src\Identity\Application\Commands\AdminUpdateUser\AdminUpdateUserCommand;
use Src\Identity\Application\Commands\BulkActivation\BulkActivationCommand;
use Src\Identity\Application\Commands\BulkAssignRoles\BulkAssignRolesCommand;
use Src\Identity\Application\Commands\BulkChangeEmail\BulkChangeEmailCommand;
use Src\Identity\Application\Commands\BulkPasswordReset\BulkPasswordResetCommand;
use Src\Identity\Application\Commands\BulkVerifyEmail\BulkVerifyEmailCommand;
use Src\Identity\Application\Queries\ExportUsers\ExportUsersQuery;
use Src\Identity\Application\Queries\GetUser\GetUserQuery;
use Src\Identity\Application\Queries\ListUsers\ListUsersQuery;
use Src\Identity\Application\Resources\AdminUserExportResource;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[OA\Tag(name: 'Identity - Admin', description: 'Administrator endpoints for user management')]
final class AdminUserController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/identity/admin/users',
        summary: 'List all users',
        description: 'Returns a paginated list of users with optional filtering and sorting.',
        operationId: 'listUsers',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15)),
            new OA\Parameter(name: 'sort_by', in: 'query', schema: new OA\Schema(type: 'string', default: 'created_at')),
            new OA\Parameter(name: 'sort_dir', in: 'query', schema: new OA\Schema(type: 'string', default: 'desc', enum: ['asc', 'desc'])),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'active', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'role', in: 'query', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Users list', content: new OA\JsonContent(
                allOf: [
                    new OA\Schema(ref: '#/components/schemas/JsonEnvelope'),
                    new OA\Schema(properties: [
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/UserResource')),
                            new OA\Property(property: 'meta', ref: '#/components/schemas/PaginatedMeta'),
                        ]),
                    ])
                ]
            )),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden - requires admin permissions'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $result = $this->queryBus->dispatch(new ListUsersQuery(
            page: (int) $request->input('page', 1),
            perPage: (int) $request->input('per_page', 15),
            sortBy: $request->input('sort_by', 'created_at'),
            sortDir: $request->input('sort_dir', 'desc'),
            search: $request->input('search'),
            active: $request->has('active') ? (bool) $request->input('active') : null,
            role: $request->input('role'),
        ));
        return ApiResponseFactory::paginated($result);
    }

    #[OA\Get(
        path: '/identity/admin/users/export',
        summary: 'Export users as CSV',
        description: 'Generates a CSV file with user data matching the provided filters.',
        operationId: 'exportUsers',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'active', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'role', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'created_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'created_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'CSV file download',
                content: new OA\MediaType(
                    mediaType: 'text/csv',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                )
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function export(ExportUsersRequest $request): StreamedResponse
    {
        set_time_limit(0);

        /** @var iterable<int, \App\Models\User> $users */
        $users = $this->queryBus->dispatch(new ExportUsersQuery(
            search: $request->validated('search'),
            active: $request->has('active') ? (bool) $request->validated('active') : null,
            role: $request->validated('role'),
            createdFrom: $request->validated('created_from'),
            createdTo: $request->validated('created_to'),
        ));

        $filename = 'users-export-' . date('Y-m-d') . '.csv';

        $response = new StreamedResponse(function () use ($users): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Header row
            fputcsv($handle, AdminUserExportResource::csvHeaders());

            $rowCount = 0;

            foreach ($users as $user) {
                /** @var ?string $phone */
                $phone = $user->getAttribute('phone');
                /** @var ?string $country */
                $country = $user->getAttribute('country');
                /** @var ?string $defaultLocale */
                $defaultLocale = $user->getAttribute('default_locale');

                $resource = new AdminUserExportResource(
                    id: $user->id,
                    name: $user->name,
                    email: $user->email,
                    phone: $phone,
                    country: $country,
                    defaultLocale: $defaultLocale,
                    active: $user->active ? 'yes' : 'no',
                    emailVerified: $user->email_verified_at !== null ? 'yes' : 'no',
                    roles: $user->roles->pluck('name')->sort()->implode(','),
                    createdAt: $user->created_at?->toIso8601String() ?? '',
                    updatedAt: $user->updated_at?->toIso8601String() ?? '',
                );

                fputcsv($handle, $resource->toCsvRow());

                $rowCount++;

                if ($rowCount % 500 === 0) {
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->headers->set('Cache-Control', 'no-store, no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }

    #[OA\Get(
        path: '/identity/admin/users/{id}',
        summary: 'Get a user by ID',
        operationId: 'getUser',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'User details', content: new OA\JsonContent(ref: '#/components/schemas/UserResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $user = $this->queryBus->dispatch(new GetUserQuery(userId: $id));
        return ApiResponseFactory::ok($user);
    }

    #[OA\Patch(
        path: '/identity/admin/users/{id}',
        summary: 'Update a user',
        operationId: 'updateUser',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true),
                    new OA\Property(property: 'country', type: 'string', nullable: true),
                    new OA\Property(property: 'default_locale', type: 'string', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'User updated', content: new OA\JsonContent(ref: '#/components/schemas/UserResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function update(AdminUpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->commandBus->dispatch(new AdminUpdateUserCommand(
            userId: $id,
            name: $request->input('name'),
            email: $request->input('email'),
            phone: $request->input('phone'),
            country: $request->input('country'),
            defaultLocale: $request->input('default_locale'),
        ));
        return ApiResponseFactory::ok($user);
    }

    #[OA\Put(
        path: '/identity/admin/users/{id}/password',
        summary: 'Set user password (admin)',
        operationId: 'adminSetPassword',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['password'],
                properties: [
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 204, description: 'Password set successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'User not found'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function setPassword(AdminSetPasswordRequest $request, int $id): Response
    {
        $this->commandBus->dispatch(new AdminSetPasswordCommand(
            userId: $id,
            hashedPassword: Hash::make($request->input('password')),
        ));
        return ApiResponseFactory::noContent();
    }

    #[OA\Patch(
        path: '/identity/admin/users/{id}/verify-email',
        summary: 'Mark user email as verified',
        operationId: 'setEmailVerified',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Email verified', content: new OA\JsonContent(ref: '#/components/schemas/UserResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
    public function setEmailVerified(int $id): JsonResponse
    {
        $user = $this->commandBus->dispatch(new AdminSetEmailVerifiedCommand(
            userId: $id,
        ));
        return ApiResponseFactory::ok($user);
    }

    #[OA\Patch(
        path: '/identity/admin/users/{id}/deactivate',
        summary: 'Deactivate a user',
        operationId: 'deactivateUser',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'User deactivated', content: new OA\JsonContent(ref: '#/components/schemas/UserResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
    public function deactivate(int $id): JsonResponse
    {
        $user = $this->commandBus->dispatch(new AdminDeactivateUserCommand(
            userId: $id,
            deactivatedBy: (int) Auth::id(),
        ));
        return ApiResponseFactory::ok($user);
    }

    #[OA\Patch(
        path: '/identity/admin/users/{id}/activate',
        summary: 'Activate a user',
        operationId: 'activateUser',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'User activated', content: new OA\JsonContent(ref: '#/components/schemas/UserResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'User not found'),
        ]
    )]
    public function activate(int $id): JsonResponse
    {
        $user = $this->commandBus->dispatch(new AdminActivateUserCommand(
            userId: $id,
            activatedBy: (int) Auth::id(),
        ));
        return ApiResponseFactory::ok($user);
    }

    #[OA\Post(
        path: '/identity/admin/users/bulk/verify-email',
        summary: 'Bulk verify user emails',
        operationId: 'bulkVerifyEmail',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_ids'],
                properties: [
                    new OA\Property(
                        property: 'user_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer')
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Bulk operation completed', content: new OA\JsonContent(ref: '#/components/schemas/BulkOperationResult')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function bulkVerifyEmail(BulkVerifyEmailRequest $request): JsonResponse
    {
        $result = $this->commandBus->dispatch(new BulkVerifyEmailCommand(
            userIds: $request->validatedUserIds(),
        ));
        return ApiResponseFactory::ok($result->toArray());
    }

    #[OA\Post(
        path: '/identity/admin/users/bulk/email',
        summary: 'Bulk change user emails',
        operationId: 'bulkChangeEmail',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['updates'],
                properties: [
                    new OA\Property(
                        property: 'updates',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'user_id', type: 'integer'),
                                new OA\Property(property: 'new_email', type: 'string', format: 'email'),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Bulk operation completed', content: new OA\JsonContent(ref: '#/components/schemas/BulkOperationResult')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function bulkChangeEmail(BulkChangeEmailRequest $request): JsonResponse
    {
        $result = $this->commandBus->dispatch(new BulkChangeEmailCommand(
            changes: $request->validatedUpdates(),
        ));
        return ApiResponseFactory::ok($result->toArray());
    }

    #[OA\Post(
        path: '/identity/admin/users/bulk/activation',
        summary: 'Bulk activate or deactivate users',
        operationId: 'bulkActivation',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_ids', 'active'],
                properties: [
                    new OA\Property(
                        property: 'user_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer')
                    ),
                    new OA\Property(property: 'active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Bulk operation completed', content: new OA\JsonContent(ref: '#/components/schemas/BulkOperationResult')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function bulkActivation(BulkActivationRequest $request): JsonResponse
    {
        $result = $this->commandBus->dispatch(new BulkActivationCommand(
            userIds: $request->validatedUserIds(),
            active: $request->validatedActive(),
            performedBy: (int) (Auth::id() ?? 0),
        ));
        return ApiResponseFactory::ok($result->toArray());
    }

    #[OA\Post(
        path: '/identity/admin/users/bulk/roles',
        summary: 'Bulk assign roles to users',
        operationId: 'bulkAssignRoles',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_ids', 'roles'],
                properties: [
                    new OA\Property(
                        property: 'user_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer')
                    ),
                    new OA\Property(
                        property: 'roles',
                        type: 'array',
                        items: new OA\Items(type: 'string')
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Bulk operation completed', content: new OA\JsonContent(ref: '#/components/schemas/BulkOperationResult')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function bulkAssignRoles(BulkAssignRolesRequest $request): JsonResponse
    {
        $result = $this->commandBus->dispatch(new BulkAssignRolesCommand(
            userIds: $request->validatedUserIds(),
            roles: $request->validatedRoles(),
        ));
        return ApiResponseFactory::ok($result->toArray());
    }

    #[OA\Post(
        path: '/identity/admin/users/bulk/password-reset',
        summary: 'Bulk reset user passwords',
        operationId: 'bulkPasswordReset',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_ids'],
                properties: [
                    new OA\Property(
                        property: 'user_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer')
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Bulk operation completed', content: new OA\JsonContent(ref: '#/components/schemas/BulkOperationResult')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function bulkPasswordReset(BulkPasswordResetRequest $request): JsonResponse
    {
        $result = $this->commandBus->dispatch(new BulkPasswordResetCommand(
            userIds: $request->validatedUserIds(),
        ));
        return ApiResponseFactory::ok($result->toArray());
    }
}
