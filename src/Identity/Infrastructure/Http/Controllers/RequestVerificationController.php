<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\RequestVerification\RequestVerificationCommand;
use Src\Identity\Infrastructure\Http\Requests\RequestVerificationRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class RequestVerificationController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/identity/email/request-verification',
        summary: 'Request email verification',
        description: 'Sends a verification email to the user.',
        operationId: 'requestVerification',
        tags: ['Identity'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Verification email sent', content: new OA\JsonContent(ref: '#/components/schemas/MessageResponse')),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function __invoke(RequestVerificationRequest $request): JsonResponse
    {
        $this->commandBus->dispatch(new RequestVerificationCommand(
            email: $request->input('email'),
        ));
        return ApiResponseFactory::ok(['message' => 'verification_requested']);
    }
}
