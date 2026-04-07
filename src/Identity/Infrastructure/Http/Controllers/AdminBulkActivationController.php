<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\BulkActivation\BulkActivationCommand;
use Src\Identity\Infrastructure\Http\Requests\BulkActivationRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AdminBulkActivationController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/identity/admin/users/bulk/activation',
        summary: 'Bulk activate or deactivate users',
        operationId: 'bulkActivation',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_ids', 'active'],
                properties: [
                    new OA\Property(
                        property: 'user_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer')
                    ),
                    new OA\Property(property: 'active', type: 'boolean'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Bulk operation completed', content: new OA\JsonContent(ref: '#/components/schemas/BulkOperationResult')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function __invoke(BulkActivationRequest $request): JsonResponse
    {
        $result = $this->commandBus->dispatch(new BulkActivationCommand(
            userIds: $request->validatedUserIds(),
            active: $request->validatedActive(),
            performedBy: (int) (Auth::id() ?? 0),
        ));
        return ApiResponseFactory::ok($result->toArray());
    }
}
