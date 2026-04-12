<?php

declare(strict_types=1);

namespace Src\Subscriptions\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;
use Src\Subscriptions\Application\Queries\GetSubscriptionType\GetSubscriptionTypeQuery;

final class AdminGetSubscriptionTypeController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/subscriptions/admin/subscription-types/{id}',
        summary: 'Get a subscription type',
        description: 'Returns the full details of a subscription type by ID.',
        operationId: 'getSubscriptionType',
        tags: ['Subscriptions'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Subscription type details', content: new OA\JsonContent(ref: '#/components/schemas/SubscriptionTypeResource')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Not found'),
        ],
    )]
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $result = $this->queryBus->dispatch(new GetSubscriptionTypeQuery(id: $id));

        return ApiResponseFactory::ok($result);
    }
}
