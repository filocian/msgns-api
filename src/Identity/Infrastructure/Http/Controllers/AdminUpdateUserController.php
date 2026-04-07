<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\AdminUpdateUser\AdminUpdateUserCommand;
use Src\Identity\Infrastructure\Http\Requests\AdminUpdateUserRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AdminUpdateUserController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

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
    public function __invoke(AdminUpdateUserRequest $request, int $id): JsonResponse
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
}
