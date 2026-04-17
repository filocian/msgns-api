<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Billing\Application\Queries\ListStripeProductPrices\ListStripeProductPricesQuery;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class ListStripeProductPricesController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/api/v2/billing/stripe/products/{productId}/prices',
        summary: 'List active prices for a Stripe product (admin catalog proxy)',
        description: 'Returns the list of active prices for the given Stripe product. Admin-only. Not cached — reflects Stripe live state.',
        operationId: 'listStripeProductPrices',
        tags: ['Billing'],
        security: [['stateful-api' => []]],
        parameters: [
            new OA\Parameter(
                name: 'productId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string', pattern: '^prod_[A-Za-z0-9]+$'),
                example: 'prod_1Abc',
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of prices',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/StripePriceResource'),
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden: missing manage_subscription_types permission'),
        ],
    )]
    public function __invoke(string $productId): JsonResponse
    {
        $result = $this->queryBus->dispatch(new ListStripeProductPricesQuery(productId: $productId));

        return ApiResponseFactory::ok($result);
    }
}
