<?php

declare(strict_types=1);

namespace Src\Identity\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Identity\Application\Commands\BulkChangeEmail\BulkChangeEmailCommand;
use Src\Identity\Infrastructure\Http\Requests\BulkChangeEmailRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class AdminBulkChangeEmailController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/identity/admin/users/bulk/email',
        summary: 'Bulk change user emails',
        operationId: 'bulkChangeEmail',
        tags: ['Identity - Admin'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['updates'],
                properties: [
                    new OA\Property(
                        property: 'updates',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'user_id', type: 'integer'),
                                new OA\Property(property: 'new_email', type: 'string', format: 'email'),
                            ]
                        )
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
    public function __invoke(BulkChangeEmailRequest $request): JsonResponse
    {
        $result = $this->commandBus->dispatch(new BulkChangeEmailCommand(
            changes: $request->validatedUpdates(),
        ));
        return ApiResponseFactory::ok($result->toArray());
    }
}
