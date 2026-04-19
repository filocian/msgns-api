<?php

declare(strict_types=1);

namespace Src\Subscriptions\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;
use Src\Subscriptions\Application\Commands\ToggleSubscriptionTypeActive\ToggleSubscriptionTypeActiveCommand;

final class AdminToggleSubscriptionTypeActiveController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Patch(
        path: '/subscriptions/admin/subscription-types/{id}/toggle-active',
        summary: 'Toggle subscription type active status',
        description: 'Flips the is_active flag for a subscription type.',
        operationId: 'toggleSubscriptionTypeActive',
        tags: ['Subscriptions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Subscription type with updated active status', content: new OA\JsonContent(ref: '#/components/schemas/SubscriptionTypeResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $result = $this->commandBus->dispatch(new ToggleSubscriptionTypeActiveCommand(id: $id));

        return ApiResponseFactory::ok($result);
    }
}
