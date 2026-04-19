<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Billing\Application\Queries\ListStripeProducts\ListStripeProductsQuery;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class ListStripeProductsController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/api/v2/billing/stripe/products',
        summary: 'List active Stripe products (admin catalog proxy)',
        description: 'Returns the list of active Stripe products with their active prices. Admin-only. Cached 5 minutes.',
        operationId: 'listStripeProducts',
        tags: ['Billing'],
        security: [['stateful-api' => []]],
		responses: [
            new OA\Response(
                response: 200,
                description: 'List of Stripe products',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/StripeProductResource'),
                        ),
                    ],
                ),
            ),
			new OA\Response(response: 401, description: 'Unauthenticated', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
			new OA\Response(response: 403, description: 'Forbidden: missing manage_subscription_types permission', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
			new OA\Response(response: 500, description: 'Stripe catalog misconfigured', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
			new OA\Response(response: 502, description: 'Stripe catalog upstream unavailable', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
		],
    )]
    public function __invoke(): JsonResponse
    {
        $result = $this->queryBus->dispatch(new ListStripeProductsQuery());

        return ApiResponseFactory::ok($result);
    }
}
