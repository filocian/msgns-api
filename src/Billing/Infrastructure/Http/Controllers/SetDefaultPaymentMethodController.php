<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Billing\Application\Commands\SetDefaultPaymentMethod\SetDefaultPaymentMethodCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class SetDefaultPaymentMethodController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Put(
        path: '/billing/me/payment-methods/{paymentMethodId}/default',
        summary: 'Set a payment method as default',
        operationId: 'setDefaultPaymentMethod',
        tags: ['Billing'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'paymentMethodId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Payment method set as default'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Payment method not found'),
        ],
    )]
    public function __invoke(Request $request, string $paymentMethodId): JsonResponse
    {
        $result = $this->commandBus->dispatch(new SetDefaultPaymentMethodCommand(
            userId: (int) $request->user()->id,
            paymentMethodId: $paymentMethodId,
        ));

        return ApiResponseFactory::ok($result);
    }
}
