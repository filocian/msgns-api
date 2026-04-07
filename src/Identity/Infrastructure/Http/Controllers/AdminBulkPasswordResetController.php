<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\BulkPasswordReset\BulkPasswordResetCommand;
use Src\Identity\Infrastructure\Http\Requests\BulkPasswordResetRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AdminBulkPasswordResetController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/identity/admin/users/bulk/password-reset',
        summary: 'Bulk reset user passwords',
        operationId: 'bulkPasswordReset',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_ids'],
                properties: [
                    new OA\Property(
                        property: 'user_ids',
                        type: 'array',
                        items: new OA\Items(type: 'integer')
                    ),
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
    public function __invoke(BulkPasswordResetRequest $request): JsonResponse
    {
        $result = $this->commandBus->dispatch(new BulkPasswordResetCommand(
            userIds: $request->validatedUserIds(),
        ));
        return ApiResponseFactory::ok($result->toArray());
    }
}
