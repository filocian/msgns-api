<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\StopImpersonation\StopImpersonationCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class StopImpersonationController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/identity/impersonate/stop',
        summary: 'Stop impersonating',
        description: 'Stops the current impersonation session and returns to the admin context.',
        operationId: 'stopImpersonation',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Impersonation stopped', content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(property: 'data', type: 'object', properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'impersonation_stopped'),
                        new OA\Property(property: 'user', ref: '#/components/schemas/UserResource'),
                    ])
                ]
            )),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $adminUserId = (int) Auth::id();
        $result = $this->commandBus->dispatch(new StopImpersonationCommand(
            adminUserId: $adminUserId,
        ));
        return ApiResponseFactory::ok($result);
    }
}
