<?php

declare(strict_types=1);

namespace Src\Instagram\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Src\Instagram\Domain\Errors\InstagramConnectionNotFound;
use Src\Instagram\Domain\Models\UserInstagramConnection;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

#[OA\Tag(name: 'Instagram', description: 'Instagram OAuth and connection management')]
#[OA\Schema(
    schema: 'InstagramConnectionResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'connected', type: 'boolean', example: true),
        new OA\Property(property: 'instagram_username', type: 'string', nullable: true, example: 'mybusiness'),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time', nullable: true, example: '2026-06-15T12:00:00Z'),
        new OA\Property(property: 'expiring_soon', type: 'boolean', example: false),
    ],
    required: ['connected', 'expiring_soon'],
)]
#[OA\Schema(
    schema: 'InstagramConnectionResponse',
    type: 'object',
    properties: [
        new OA\Property(property: 'data', ref: '#/components/schemas/InstagramConnectionResource'),
    ],
    required: ['data'],
)]
final class InstagramConnectionController extends Controller
{
    #[OA\Get(
        path: '/instagram/connection',
        summary: 'Get Instagram connection status',
        description: "Returns the current user's Instagram connection status including token expiry information. Always returns 200 — the `connected` field indicates whether a connection exists.",
        operationId: 'getInstagramConnection',
        tags: ['Instagram'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Connection status',
                content: new OA\JsonContent(ref: '#/components/schemas/InstagramConnectionResponse'),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function show(Request $request): JsonResponse
    {
        /** @var UserInstagramConnection|null $connection */
        $connection = UserInstagramConnection::where('user_id', (int) $request->user()->id)->first();

        if ($connection === null) {
            return ApiResponseFactory::ok([
                'connected'          => false,
                'instagram_username' => null,
                'expires_at'         => null,
                'expiring_soon'      => false,
            ]);
        }

        return ApiResponseFactory::ok([
            'connected'          => true,
            'instagram_username' => $connection->instagram_username,
            'expires_at'         => $connection->expires_at?->toIso8601String(),
            'expiring_soon'      => $connection->isExpiringSoon(),
        ]);
    }

    #[OA\Delete(
        path: '/instagram/connection',
        summary: 'Disconnect Instagram account',
        description: "Removes the current user's Instagram connection and deletes the stored access token.",
        operationId: 'deleteInstagramConnection',
        tags: ['Instagram'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 204, description: 'Instagram account disconnected'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(
                response: 404,
                description: 'No Instagram connection found',
                content: new OA\JsonContent(ref: '#/components/schemas/DomainError'),
            ),
        ],
    )]
    public function destroy(Request $request): Response
    {
        $connection = UserInstagramConnection::where('user_id', (int) $request->user()->id)->first();

        if ($connection === null) {
            throw InstagramConnectionNotFound::forUser((int) $request->user()->id);
        }

        $connection->delete();

        return ApiResponseFactory::noContent();
    }
}
