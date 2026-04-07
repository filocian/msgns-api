<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\VerifyEmail\VerifyEmailCommand;
use Src\Identity\Infrastructure\Http\Requests\VerifyEmailRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class VerifyEmailController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/identity/email/verify',
        summary: 'Verify email address',
        description: 'Verifies a user\'s email address using a token.',
        operationId: 'verifyEmail',
        tags: ['Identity'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['token'],
                properties: [
                    new OA\Property(property: 'token', type: 'string', description: 'Verification token'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Email verified successfully', content: new OA\JsonContent(ref: '#/components/schemas/UserResource')),
            new OA\Response(response: 422, description: 'Invalid or expired token'),
        ]
    )]
    public function __invoke(VerifyEmailRequest $request): JsonResponse
    {
        $user = $this->commandBus->dispatch(new VerifyEmailCommand(
            token: $request->input('token'),
        ));
        return ApiResponseFactory::ok($user);
    }
}
