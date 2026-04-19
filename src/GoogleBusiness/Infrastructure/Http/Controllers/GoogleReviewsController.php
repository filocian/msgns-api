<?php

declare(strict_types=1);

namespace Src\GoogleBusiness\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;
use Src\GoogleBusiness\Application\Commands\GenerateGoogleReviewResponse\GenerateGoogleReviewResponseCommand;
use Src\GoogleBusiness\Domain\Errors\GoogleBusinessConnectionNotFound;
use Src\GoogleBusiness\Domain\Ports\GoogleBusinessApiPort;
use Src\GoogleBusiness\Domain\Ports\GoogleBusinessConnectionRepositoryPort;
use Src\GoogleBusiness\Infrastructure\Http\Requests\GenerateGoogleReviewResponseRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

#[OA\Schema(
    schema: 'GoogleReview',
    type: 'object',
    properties: [
        new OA\Property(property: 'review_id', type: 'string', example: 'rev-abc'),
        new OA\Property(property: 'reviewer_display_name', type: 'string', nullable: true, example: 'Jane Doe'),
        new OA\Property(property: 'comment', type: 'string', nullable: true, example: 'Great service!'),
        new OA\Property(property: 'star_rating', type: 'string', example: 'FIVE'),
        new OA\Property(property: 'create_time', type: 'string', format: 'date-time', nullable: true),
    ],
    required: ['review_id', 'star_rating'],
)]
final class GoogleReviewsController extends Controller
{
    public function __construct(
        private readonly GoogleBusinessConnectionRepositoryPort $connections,
        private readonly GoogleBusinessApiPort $api,
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Get(
        path: '/api/v2/ai/google/reviews',
        summary: 'List pending Google Business reviews for the authenticated user',
        description: 'Fetches reviews that have no owner reply yet from the user\'s connected Google Business location. Refreshes the access token transparently when expired.',
        operationId: 'listGoogleReviews',
        tags: ['Google Reviews AI'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of pending reviews',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GoogleReview')),
                            ],
                        ),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden — no AI permission'),
            new OA\Response(response: 404, description: 'No Google Business connection', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
            new OA\Response(response: 429, description: 'AI quota exhausted'),
            new OA\Response(response: 502, description: 'Google Business API unavailable', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
        ],
    )]
    public function index(Request $request): JsonResponse
    {
        $userId = (int) Auth::id();

        $connection = $this->connections->findByUserId($userId);

        if ($connection === null) {
            throw GoogleBusinessConnectionNotFound::because('google_business_connection_not_found');
        }

        $accessToken = (string) $connection->access_token;

        if ($connection->isTokenExpired()) {
            $refreshed   = $this->api->refreshAccessToken((string) $connection->refresh_token);
            $accessToken = $refreshed['access_token'];
            $this->connections->updateTokens($userId, $accessToken, $refreshed['expires_in']);
        }

        $raw = $this->api->fetchPendingReviews(
            $accessToken,
            (string) $connection->google_account_id,
            (string) $connection->business_location_id,
        );

        $items = array_map(fn (array $review): array => [
            'review_id'             => (string) ($review['reviewId'] ?? $review['name'] ?? ''),
            'reviewer_display_name' => isset($review['reviewer']['displayName']) ? (string) $review['reviewer']['displayName'] : null,
            'comment'               => isset($review['comment']) ? (string) $review['comment'] : null,
            'star_rating'           => (string) ($review['starRating'] ?? ''),
            'create_time'           => isset($review['createTime']) ? (string) $review['createTime'] : null,
        ], $raw);

        return ApiResponseFactory::ok(['data' => $items]);
    }

    #[OA\Post(
        path: '/api/v2/ai/google/reviews/{reviewId}/generate',
        summary: 'Generate an AI reply draft for a specific Google review',
        description: 'Composes a prompt from the review text, star rating, product context, and user system prompt, then persists a pending AiResponse record.',
        operationId: 'generateGoogleReviewResponse',
        tags: ['Google Reviews AI'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'reviewId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'product_id', type: 'integer', minimum: 1),
                    new OA\Property(property: 'review_text', type: 'string', minLength: 1, maxLength: 10000),
                    new OA\Property(property: 'star_rating', type: 'integer', minimum: 1, maximum: 5),
                ],
                required: ['product_id', 'review_text', 'star_rating'],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'AI reply created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/AiResponseResource'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Product not owned by the authenticated user', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
            new OA\Response(response: 422, description: 'Validation error or duplicate pending response', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
            new OA\Response(response: 429, description: 'AI quota exhausted'),
        ],
    )]
    public function generate(GenerateGoogleReviewResponseRequest $request, string $reviewId): JsonResponse
    {
        $userId = (int) Auth::id();

        /** @var AiResponseRecordModel $record */
        $record = $this->commandBus->dispatch(new GenerateGoogleReviewResponseCommand(
            userId: $userId,
            productId: (int) $request->validated('product_id'),
            reviewId: $reviewId,
            reviewText: (string) $request->validated('review_text'),
            starRating: (int) $request->validated('star_rating'),
        ));

        return ApiResponseFactory::created([
            'id'             => (string) $record->id,
            'product_type'   => (string) $record->product_type,
            'product_id'     => (int) $record->product_id,
            'ai_content'     => (string) $record->ai_content,
            'edited_content' => $record->edited_content,
            'status'         => (string) $record->status,
            'metadata'       => $record->metadata ?? [],
            'expires_at'     => $record->expires_at->toISOString(),
            'created_at'     => $record->created_at?->toISOString(),
        ]);
    }
}
