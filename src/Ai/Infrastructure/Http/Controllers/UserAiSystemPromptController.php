<?php

declare(strict_types=1);

namespace Src\Ai\Infrastructure\Http\Controllers;

use App\Http\Contracts\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Src\Ai\Application\Commands\DeleteUserSystemPrompt\DeleteUserSystemPromptCommand;
use Src\Ai\Application\Commands\UpsertUserSystemPrompt\UpsertUserSystemPromptCommand;
use Src\Ai\Application\Queries\GetUserSystemPrompts\GetUserSystemPromptsQuery;
use Src\Ai\Domain\Entities\UserAiSystemPrompt;
use Src\Ai\Domain\ValueObjects\AiProductType;
use Src\Ai\Infrastructure\Http\Requests\DeleteSystemPromptRequest;
use Src\Ai\Infrastructure\Http\Requests\UpsertSystemPromptRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

#[OA\Schema(
    schema: 'AiProductTypeEnum',
    type: 'string',
    enum: ['google_review', 'instagram_content'],
)]
#[OA\Schema(
    schema: 'UserAiSystemPromptResource',
    type: 'object',
    properties: [
        new OA\Property(property: 'product_type', ref: '#/components/schemas/AiProductTypeEnum'),
        new OA\Property(property: 'prompt_text', type: 'string'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
    ],
    required: ['product_type', 'prompt_text', 'updated_at'],
)]
final class UserAiSystemPromptController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
        private readonly CommandBus $commandBus,
    ) {}

    #[OA\Get(
        path: '/ai/system-prompts',
        summary: 'List user AI system prompts',
        description: 'Returns all AI system prompts configured by the authenticated user, keyed by product type.',
        operationId: 'listUserAiSystemPrompts',
        tags: ['AI'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of system prompts',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/UserAiSystemPromptResource'),
                        ),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function index(): JsonResponse
    {
        /** @var UserAiSystemPrompt[] $prompts */
        $prompts = $this->queryBus->dispatch(new GetUserSystemPromptsQuery(userId: (int) Auth::id()));

        $data = array_map(fn (UserAiSystemPrompt $p) => [
            'product_type' => $p->productType->value,
            'prompt_text' => $p->promptText,
            'updated_at' => $p->updatedAt->format('Y-m-d\TH:i:s.u\Z'),
        ], $prompts);

        return ApiResponseFactory::ok($data);
    }

    #[OA\Put(
        path: '/ai/system-prompts/{product_type}',
        summary: 'Upsert a user AI system prompt',
        description: 'Creates or updates the AI system prompt for a given product type.',
        operationId: 'upsertUserAiSystemPrompt',
        tags: ['AI'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'product_type',
                in: 'path',
                required: true,
                schema: new OA\Schema(ref: '#/components/schemas/AiProductTypeEnum'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'prompt_text', type: 'string', description: 'The system prompt text (max 1000 words)'),
                ],
                required: ['prompt_text'],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Prompt upserted successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/UserAiSystemPromptResource'),
                    ],
                    type: 'object',
                ),
            ),
            new OA\Response(response: 400, description: 'Validation error — invalid product_type or missing/too-long prompt_text'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
        ],
    )]
    public function upsert(UpsertSystemPromptRequest $request): JsonResponse
    {
        /** @var UserAiSystemPrompt $prompt */
        $prompt = $this->commandBus->dispatch(new UpsertUserSystemPromptCommand(
            userId: (int) Auth::id(),
            productType: AiProductType::from((string) $request->validated('product_type')),
            promptText: (string) $request->validated('prompt_text'),
        ));

        return ApiResponseFactory::ok([
            'product_type' => $prompt->productType->value,
            'prompt_text' => $prompt->promptText,
            'updated_at' => $prompt->updatedAt->format('Y-m-d\TH:i:s.u\Z'),
        ]);
    }

    #[OA\Delete(
        path: '/ai/system-prompts/{product_type}',
        summary: 'Delete a user AI system prompt',
        description: 'Removes the AI system prompt for a given product type. Returns 404 if no prompt exists.',
        operationId: 'deleteUserAiSystemPrompt',
        tags: ['AI'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'product_type',
                in: 'path',
                required: true,
                schema: new OA\Schema(ref: '#/components/schemas/AiProductTypeEnum'),
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Prompt deleted successfully'),
            new OA\Response(response: 400, description: 'Validation error — invalid product_type'),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Prompt not found for this product type', content: new OA\JsonContent(ref: '#/components/schemas/DomainError')),
        ],
    )]
    public function destroy(DeleteSystemPromptRequest $request): Response
    {
        $this->commandBus->dispatch(new DeleteUserSystemPromptCommand(
            userId: (int) Auth::id(),
            productType: AiProductType::from((string) $request->validated('product_type')),
        ));

        return ApiResponseFactory::noContent();
    }
}
