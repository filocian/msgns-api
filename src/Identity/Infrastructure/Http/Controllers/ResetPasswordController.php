<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\ResetPassword\ResetPasswordCommand;
use Src\Identity\Infrastructure\Http\Requests\ResetPasswordRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class ResetPasswordController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/identity/password/reset',
        summary: 'Reset password',
        description: 'Resets the user\'s password using a token.',
        operationId: 'resetPassword',
        tags: ['Identity'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token', 'password'],
                properties: [
                    new OA\Property(property: 'token', type: 'string'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Password reset successful', content: new OA\JsonContent(ref: '#/components/schemas/UserResource')),
            new OA\Response(response: 422, description: 'Invalid or expired token'),
        ]
    )]
    public function __invoke(ResetPasswordRequest $request): JsonResponse
    {
        $user = $this->commandBus->dispatch(new ResetPasswordCommand(
            token: $request->input('token'),
            newHashedPassword: Hash::make($request->input('password')),
        ));
        return ApiResponseFactory::ok($user);
    }
}
