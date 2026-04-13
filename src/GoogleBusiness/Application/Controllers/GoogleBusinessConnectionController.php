<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Application\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Src\GoogleBusiness\Application\Commands\DisconnectGoogleBusiness\DisconnectGoogleBusinessCommand;
use Src\GoogleBusiness\Application\Queries\GetGoogleBusinessConnection\GetGoogleBusinessConnectionQuery;
use Src\GoogleBusiness\Domain\Models\UserGoogleBusinessConnection;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

#[OA\Schema(
    schema: 'GoogleBusinessConnectionResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'connected', type: 'boolean'),
        new OA\Property(property: 'google_account_id', type: 'string', nullable: true),
        new OA\Property(property: 'business_location_id', type: 'string', nullable: true),
        new OA\Property(property: 'business_name', type: 'string', nullable: true),
    ],
    required: ['connected', 'google_account_id', 'business_location_id', 'business_name'],
)]
final class GoogleBusinessConnectionController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Get(
        path: '/google-business/connection',
        summary: 'Get Google Business connection status',
        description: 'Returns the Google Business connection status for the authenticated user.',
        operationId: 'getGoogleBusinessConnection',
        tags: ['Google Business'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connection status',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/GoogleBusinessConnectionResource'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function show(Request $request): JsonResponse
    {
        /** @var UserGoogleBusinessConnection|null $connection */
        $connection = $this->queryBus->dispatch(new GetGoogleBusinessConnectionQuery(
            userId: (int) $request->user()->id,
        ));

        if ($connection === null) {
            return ApiResponseFactory::ok([
                'connected'           => false,
                'google_account_id'   => null,
                'business_location_id' => null,
                'business_name'        => null,
            ]);
        }

        return ApiResponseFactory::ok([
            'connected'           => true,
            'google_account_id'   => $connection->google_account_id,
            'business_location_id' => $connection->business_location_id,
            'business_name'        => $connection->business_name,
        ]);
    }

    #[OA\Delete(
        path: '/google-business/connection',
        summary: 'Disconnect Google Business account',
        description: 'Removes the Google Business OAuth connection for the authenticated user.',
        operationId: 'deleteGoogleBusinessConnection',
        tags: ['Google Business'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 204, description: 'Connection deleted'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function destroy(Request $request): Response
    {
        $this->commandBus->dispatch(new DisconnectGoogleBusinessCommand(
            userId: (int) $request->user()->id,
        ));

        return ApiResponseFactory::noContent();
    }
}
