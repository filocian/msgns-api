<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\Logout\LogoutCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class LogoutController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/identity/logout',
        summary: 'Logout current user',
        description: 'Logs out the authenticated user and invalidates the session.',
        operationId: 'logout',
        tags: ['Identity'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 204, description: 'Logged out successfully'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function __invoke(Request $request): Response
    {
        $userId = (int) Auth::id();
        Auth::guard('stateful-api')->logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
        $this->commandBus->dispatch(new LogoutCommand(userId: $userId));
        return ApiResponseFactory::noContent();
    }
}
