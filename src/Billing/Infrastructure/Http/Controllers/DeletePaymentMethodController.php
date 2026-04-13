<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Src\Billing\Application\Commands\DeletePaymentMethod\DeletePaymentMethodCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class DeletePaymentMethodController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Delete(
        path: '/billing/me/payment-methods/{paymentMethodId}',
        summary: 'Delete a payment method',
        operationId: 'deletePaymentMethod',
        tags: ['Billing'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'paymentMethodId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Payment method deleted'),
            new OA\Response(response: 400, description: 'Cannot delete default payment method with active subscriptions'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Payment method not found'),
        ],
    )]
    public function __invoke(Request $request, string $paymentMethodId): Response
    {
        $this->commandBus->dispatch(new DeletePaymentMethodCommand(
            userId: (int) $request->user()->id,
            paymentMethodId: $paymentMethodId,
        ));

        return ApiResponseFactory::noContent();
    }
}
