<?php

declare(strict_types=1);

namespace Src\Subscriptions\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;
use Src\Subscriptions\Application\Commands\DeleteSubscriptionType\DeleteSubscriptionTypeCommand;

final class AdminDeleteSubscriptionTypeController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Delete(
        path: '/subscriptions/admin/subscription-types/{id}',
        summary: 'Delete a subscription type',
        description: 'Soft-deletes a subscription type. Returns 409 if active subscriptions exist.',
        operationId: 'deleteSubscriptionType',
        tags: ['Subscriptions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Subscription type deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
            new OA\Response(response: 409, description: 'Conflict — active subscriptions exist'),
        ],
    )]
    public function __invoke(Request $request, int $id): Response
    {
        $this->commandBus->dispatch(new DeleteSubscriptionTypeCommand(id: $id));

        return ApiResponseFactory::noContent();
    }
}
