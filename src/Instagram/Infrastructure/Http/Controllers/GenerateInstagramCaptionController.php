<?php

declare(strict_types=1);

namespace Src\Instagram\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;
use Src\Instagram\Application\Commands\GenerateInstagramCaption\GenerateInstagramCaptionCommand;
use Src\Instagram\Infrastructure\Http\Requests\GenerateInstagramCaptionRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class GenerateInstagramCaptionController extends Controller
{
    public function __construct(private readonly CommandBus $commandBus) {}

    #[OA\Post(
        path: '/api/v2/ai/instagram/generate',
        summary: 'Generate an AI-drafted Instagram caption',
        description: 'Composes a prompt from the product context, user system prompt, and optional uploaded image, then persists a pending AiResponse record. When an image is supplied, it is uploaded to S3 and the public URL is stored in metadata.s3_image_url for later publishing via /apply.',
        operationId: 'generateInstagramCaption',
        tags: ['Instagram AI'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'product_id', type: 'integer', minimum: 1),
                    new OA\Property(property: 'image_base64', type: 'string', nullable: true, description: 'Base64-encoded image data. Required when image_mime_type is present.'),
                    new OA\Property(property: 'image_mime_type', type: 'string', nullable: true, enum: ['image/jpeg', 'image/png', 'image/webp']),
                    new OA\Property(property: 'context', type: 'string', nullable: true, maxLength: 2000),
                ],
                required: ['product_id'],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Caption draft created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/AiResponseResource'),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 402, description: 'Payment required'),
            new OA\Response(response: 403, description: 'Product not owned by the authenticated user', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
            new OA\Response(response: 422, description: 'Validation error', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
            new OA\Response(response: 429, description: 'AI quota exhausted or rate-limited'),
            new OA\Response(response: 502, description: 'Media upload or downstream service failure', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
        ],
    )]
    public function __invoke(GenerateInstagramCaptionRequest $request): JsonResponse
    {
        $userId = (int) Auth::id();

        /** @var AiResponseRecordModel $record */
        $record = $this->commandBus->dispatch(new GenerateInstagramCaptionCommand(
            userId:        $userId,
            productId:     (int) $request->validated('product_id'),
            imageBase64:   $request->validated('image_base64'),
            imageMimeType: $request->validated('image_mime_type'),
            context:       $request->validated('context'),
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
