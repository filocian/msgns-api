<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\CancelPendingEmailChange\CancelPendingEmailChangeCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class CancelPendingEmailChangeController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Delete(
        path: '/identity/me/email/pending',
        summary: 'Cancel pending email change',
        description: 'Cancels a pending email change for the authenticated user.',
        operationId: 'cancelPendingEmailChange',
        tags: ['Identity'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 204, description: 'Email change cancelled'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function __invoke(Request $request): Response
    {
        $userId = (int) Auth::id();
        $this->commandBus->dispatch(new CancelPendingEmailChangeCommand(
            userId: $userId,
        ));
        return ApiResponseFactory::noContent();
    }
}
