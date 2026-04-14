<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Ai\Application\Commands\PurchasePrepaidPackage\PurchasePrepaidPackageCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

#[OA\Schema(
    schema: 'PurchasePrepaidPackageRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'package_id', type: 'integer', description: 'ID of the prepaid package to purchase'),
        new OA\Property(property: 'payment_method_id', type: 'string', description: 'Stripe Payment Method ID (pm_...)'),
    ],
    required: ['package_id', 'payment_method_id'],
)]
#[OA\Schema(
    schema: 'PrepaidPurchaseSucceededResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['succeeded']),
        new OA\Property(property: 'balance', type: 'object', description: 'The newly created prepaid balance row'),
    ],
    required: ['status', 'balance'],
)]
#[OA\Schema(
    schema: 'PrepaidPurchaseRequiresActionResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['requires_action']),
        new OA\Property(property: 'client_secret', type: 'string', description: 'Stripe client_secret for Stripe.js confirmCardPayment()'),
    ],
    required: ['status', 'client_secret'],
)]
final class PurchasePrepaidPackageController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/ai/packages/purchase',
        summary: 'Purchase a prepaid AI package',
        description: 'Performs a one-time Stripe charge and creates a prepaid balance for the authenticated user.',
        operationId: 'purchasePrepaidPackage',
        tags: ['AI Prepaid Packages'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PurchasePrepaidPackageRequest'),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Payment succeeded — balance created and permission granted',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'data', ref: '#/components/schemas/PrepaidPurchaseSucceededResource')],
                    type: 'object',
                ),
            ),
            new OA\Response(
                response: 200,
                description: 'Payment requires 3DS authentication — includes client_secret for Stripe.js confirmCardPayment()',
                content: new OA\JsonContent(ref: '#/components/schemas/PrepaidPurchaseRequiresActionResource'),
            ),
            new OA\Response(
                response: 422,
                description: 'Payment declined',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', enum: ['failed']),
                        new OA\Property(property: 'message', type: 'string'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 404, description: 'Package not found', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'package_id'        => ['required', 'integer'],
            'payment_method_id' => ['required', 'string'],
        ]);

        $userId = (int) $request->user()?->id;

        /** @var array{status: string, balance?: mixed, client_secret?: string, message?: string} $result */
        $result = $this->commandBus->dispatch(new PurchasePrepaidPackageCommand(
            packageId:       (int) $validated['package_id'],
            paymentMethodId: (string) $validated['payment_method_id'],
            userId:          $userId,
        ));

        return match ($result['status']) {
            'succeeded'       => ApiResponseFactory::created($result),
            'requires_action' => response()->json($result),
            default           => response()->json($result, 422),
        };
    }
}
