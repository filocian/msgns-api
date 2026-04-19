<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Ai\Application\Queries\GetPrepaidBalances\GetPrepaidBalancesQuery;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class GetPrepaidBalancesController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/ai/packages/balances',
        summary: 'Get authenticated user prepaid balances',
        description: 'Returns all prepaid balance rows with requests_remaining > 0 for the authenticated user, ordered by purchase date ascending (FIFO).',
        operationId: 'getPrepaidBalances',
        tags: ['AI Prepaid Packages'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of active prepaid balances',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(type: 'object')),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $balances = $this->queryBus->dispatch(new GetPrepaidBalancesQuery(
            userId: (int) $request->user()?->id,
        ));

        return ApiResponseFactory::ok($balances);
    }
}
