<?php

declare(strict_types=1);

namespace Src\Subscriptions\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;
use Src\Subscriptions\Application\Queries\ListPublicSubscriptionTypes\ListPublicSubscriptionTypesQuery;

final class ListPublicSubscriptionTypesController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/subscriptions/subscription-types',
        summary: 'List public subscription types',
        description: 'Returns all active, non-deleted subscription types. No authentication required.',
        operationId: 'listPublicSubscriptionTypes',
        tags: ['Subscriptions (Public)'],
        responses: [
            new OA\Response(response: 200, description: 'Public subscription types list', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/PublicSubscriptionTypeResource')),
                ]
            )),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->queryBus->dispatch(new ListPublicSubscriptionTypesQuery());

        return ApiResponseFactory::ok($result);
    }
}
