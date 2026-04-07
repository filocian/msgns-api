<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Queries\GetCurrentUser\GetCurrentUserQuery;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class GetCurrentUserController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/identity/me',
        summary: 'Get current user',
        description: 'Returns the authenticated user\'s profile information.',
        operationId: 'getCurrentUser',
        tags: ['Identity'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'User profile', content: new OA\JsonContent(ref: '#/components/schemas/UserResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $userId = (int) Auth::id();
        $user = $this->queryBus->dispatch(new GetCurrentUserQuery(userId: $userId));
        return ApiResponseFactory::ok($user);
    }
}
