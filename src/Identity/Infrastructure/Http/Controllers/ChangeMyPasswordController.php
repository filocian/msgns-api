<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\ChangeMyPassword\ChangeMyPasswordCommand;
use Src\Identity\Infrastructure\Http\Requests\ChangeMyPasswordRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class ChangeMyPasswordController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Patch(
        path: '/identity/me/password',
        summary: 'Change current user password',
        description: 'Changes the authenticated user\'s password.',
        operationId: 'changeMyPassword',
        tags: ['Identity'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['current_password', 'new_password'],
                properties: [
                    new OA\Property(property: 'current_password', type: 'string', format: 'password'),
                    new OA\Property(property: 'new_password', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 204, description: 'Password changed successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated or invalid current password'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function __invoke(ChangeMyPasswordRequest $request): Response
    {
        $userId = (int) Auth::id();
        $this->commandBus->dispatch(new ChangeMyPasswordCommand(
            userId: $userId,
            currentPassword: $request->input('current_password'),
            newHashedPassword: Hash::make($request->input('new_password')),
        ));
        return ApiResponseFactory::noContent();
    }
}
