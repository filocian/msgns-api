<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Ai\Application\Commands\ApplyAiResponse\ApplyAiResponseCommand;
use Src\Ai\Application\Commands\ApproveAiResponse\ApproveAiResponseCommand;
use Src\Ai\Application\Commands\EditAiResponse\EditAiResponseCommand;
use Src\Ai\Application\Commands\RejectAiResponse\RejectAiResponseCommand;
use Src\Ai\Application\Queries\ListAiResponses\ListAiResponsesQuery;
use Src\Ai\Infrastructure\Http\Requests\EditAiResponseRequest;
use Src\Ai\Infrastructure\Http\Requests\ListAiResponsesRequest;
use Src\Ai\Infrastructure\Persistence\AiResponseRecordModel;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\PaginatedResult;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

#[OA\Schema(
    schema: 'AiResponseResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'product_type', type: 'string'),
        new OA\Property(property: 'product_id', type: 'integer'),
        new OA\Property(property: 'ai_content', type: 'string'),
        new OA\Property(property: 'edited_content', type: 'string', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'approved', 'edited', 'rejected', 'applying', 'applied', 'expired']),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'applied_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    required: ['id', 'product_type', 'product_id', 'ai_content', 'status', 'expires_at'],
)]
final class AiResponseController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Get(
        path: '/api/v2/ai/responses',
        summary: 'List AI responses for the authenticated user',
        description: 'Returns a paginated list of AI responses scoped to the authenticated user. Filterable by status and product type.',
        operationId: 'listAiResponses',
        tags: ['AI Responses'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['pending', 'approved', 'edited', 'rejected', 'applying', 'applied', 'expired'])),
            new OA\Parameter(name: 'product_type', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Paginated list of AI responses',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AiResponseResource')),
                        new OA\Property(property: 'meta', properties: [
                            new OA\Property(property: 'currentPage', type: 'integer'),
                            new OA\Property(property: 'lastPage', type: 'integer'),
                            new OA\Property(property: 'perPage', type: 'integer'),
                            new OA\Property(property: 'total', type: 'integer'),
                        ], type: 'object'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function index(ListAiResponsesRequest $request): JsonResponse
    {
        /** @var PaginatedResult $result */
        $result = $this->queryBus->dispatch(new ListAiResponsesQuery(
            userId: (int) Auth::id(),
            page: (int) ($request->validated('page') ?? 1),
            perPage: (int) ($request->validated('per_page') ?? 15),
            status: $request->validated('status') !== null ? (string) $request->validated('status') : null,
            productType: $request->validated('product_type') !== null ? (string) $request->validated('product_type') : null,
        ));

        return ApiResponseFactory::ok([
            'data' => array_map(fn (AiResponseRecordModel $r) => $this->formatRecord($r), $result->items),
            'meta' => [
                'currentPage' => $result->currentPage,
                'lastPage'    => $result->lastPage,
                'perPage'     => $result->perPage,
                'total'       => $result->total,
            ],
        ]);
    }

    #[OA\Patch(
        path: '/api/v2/ai/responses/{id}/approve',
        summary: 'Approve an AI response',
        description: 'Transitions an AI response from pending or edited to approved.',
        operationId: 'approveAiResponse',
        tags: ['AI Responses'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Response approved'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'AI response not found', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
            new OA\Response(response: 422, description: 'Invalid status transition', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
        ],
    )]
    public function approve(string $id): Response
    {
        $this->commandBus->dispatch(new ApproveAiResponseCommand(
            id: $id,
            userId: (int) Auth::id(),
        ));

        return ApiResponseFactory::noContent();
    }

    #[OA\Patch(
        path: '/api/v2/ai/responses/{id}/edit',
        summary: 'Edit an AI response',
        description: 'Stores edited content and transitions the response to edited status. Idempotent if already edited.',
        operationId: 'editAiResponse',
        tags: ['AI Responses'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'edited_content', type: 'string', minLength: 1, maxLength: 10000),
                ],
                required: ['edited_content'],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Response edited'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'AI response not found', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
            new OA\Response(response: 422, description: 'Validation error or invalid status transition', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
        ],
    )]
    public function edit(EditAiResponseRequest $request, string $id): Response
    {
        $this->commandBus->dispatch(new EditAiResponseCommand(
            id: $id,
            userId: (int) Auth::id(),
            editedContent: (string) $request->validated('edited_content'),
        ));

        return ApiResponseFactory::noContent();
    }

    #[OA\Patch(
        path: '/api/v2/ai/responses/{id}/reject',
        summary: 'Reject an AI response',
        description: 'Transitions an AI response from pending or edited to rejected (terminal state).',
        operationId: 'rejectAiResponse',
        tags: ['AI Responses'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Response rejected'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'AI response not found', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
            new OA\Response(response: 422, description: 'Invalid status transition', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
        ],
    )]
    public function reject(string $id): Response
    {
        $this->commandBus->dispatch(new RejectAiResponseCommand(
            id: $id,
            userId: (int) Auth::id(),
        ));

        return ApiResponseFactory::noContent();
    }

    #[OA\Post(
        path: '/api/v2/ai/responses/{id}/apply',
        summary: 'Apply an AI response to the target platform',
        description: 'Delegates publishing to the appropriate applier. Synchronous appliers (e.g. Google Reviews) transition directly to `applied` inside a transaction that rolls back on failure. Asynchronous appliers (e.g. Instagram) transition to `applying` and hand off to a queued job that will transition to `applied` when the remote publish completes; on retry exhaustion the record is reset to `approved` so the client can retry.',
        operationId: 'applyAiResponse',
        tags: ['AI Responses'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Response accepted. Poll GET /api/v2/ai/responses/{id} to observe status transitions (applied / applying / back to approved on failure).'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'AI response not found or no applier for product type', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
            new OA\Response(response: 422, description: 'Invalid status transition', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
        ],
    )]
    public function apply(string $id): Response
    {
        $this->commandBus->dispatch(new ApplyAiResponseCommand(
            id: $id,
            userId: (int) Auth::id(),
        ));

        return ApiResponseFactory::noContent();
    }

    /** @return array<string, mixed> */
    private function formatRecord(AiResponseRecordModel $record): array
    {
        return [
            'id'             => $record->id,
            'product_type'   => $record->product_type,
            'product_id'     => $record->product_id,
            'ai_content'     => $record->ai_content,
            'edited_content' => $record->edited_content,
            'status'         => $record->status,
            'expires_at'     => $record->expires_at->toISOString(),
            'applied_at'     => $record->applied_at?->toISOString(),
            'created_at'     => $record->created_at?->toISOString(),
            'updated_at'     => $record->updated_at?->toISOString(),
        ];
    }
}
