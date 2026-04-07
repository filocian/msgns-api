<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\AdminSetPassword\AdminSetPasswordCommand;
use Src\Identity\Infrastructure\Http\Requests\AdminSetPasswordRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AdminSetPasswordController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

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
    public function __invoke(AdminSetPasswordRequest $request, int $id): Response
    {
        $this->commandBus->dispatch(new AdminSetPasswordCommand(
            userId: $id,
            hashedPassword: Hash::make($request->input('password')),
        ));
        return ApiResponseFactory::noContent();
    }
}
