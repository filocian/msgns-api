<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Billing\Application\Queries\ListPaymentMethods\ListPaymentMethodsQuery;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class ListPaymentMethodsController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/billing/me/payment-methods',
        summary: 'List payment methods for authenticated user',
        description: 'Returns all saved payment methods for the authenticated user.',
        operationId: 'listPaymentMethods',
        tags: ['Billing'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'List of payment methods'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->queryBus->dispatch(new ListPaymentMethodsQuery(
            userId: (int) $request->user()->id,
        ));

        return ApiResponseFactory::ok($result);
    }
}
