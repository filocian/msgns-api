<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Src\Billing\Application\Commands\CreateSetupIntent\CreateSetupIntentCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class CreateSetupIntentController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Post(
        path: '/billing/me/setup-intent',
        summary: 'Create a Stripe SetupIntent for saving a payment method',
        description: 'Creates or retrieves a Stripe customer and returns a client_secret for the SetupIntent.',
        operationId: 'createSetupIntent',
        tags: ['Billing'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'SetupIntent created with client_secret'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $result = $this->commandBus->dispatch(new CreateSetupIntentCommand(
            userId: (int) $request->user()->id,
        ));

        return ApiResponseFactory::ok($result);
    }
}
