<?php

declare(strict_types=1);

namespace App\Http\Controllers\Products;

use App\Http\Contracts\Controller;
use App\Http\OpenApi\Schemas as OpenApiSchemas;
use App\Http\Requests\Products\ListGenerationHistoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Products\Application\Queries\DownloadGenerationExcel\DownloadGenerationExcelQuery;
use Src\Products\Application\Queries\ListGenerationHistory\ListGenerationHistoryQuery;
use Src\Shared\Core\Bus\QueryBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

#[OA\Tag(name: 'Products', description: 'Product generation history endpoints')]
final class GenerationHistoryController extends Controller
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

    #[OA\Get(
        path: '/products/generations',
        summary: 'List product generation history',
        operationId: 'listGenerationHistory',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, maximum: 50)),
            new OA\Parameter(name: 'timezone', in: 'query', schema: new OA\Schema(type: 'string', default: 'UTC')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Generation history list', content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/' . OpenApiSchemas::GENERATION_HISTORY_LIST_ITEM)),
                    new OA\Property(property: 'meta', ref: '#/components/schemas/PaginatedMeta'),
                ],
                type: 'object',
            )),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function index(ListGenerationHistoryRequest $request): JsonResponse
    {
        $result = $this->queryBus->dispatch(new ListGenerationHistoryQuery(
            page: (int) $request->validated('page', 1),
            perPage: (int) $request->validated('per_page', 15),
            timezone: (string) $request->validated('timezone', 'UTC'),
        ));

        return ApiResponseFactory::paginated($result);
    }

    #[OA\Get(
        path: '/products/generations/{id}/download',
        summary: 'Download generation Excel sheet',
        operationId: 'downloadGenerationExcel',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Generation Excel file',
                content: new OA\MediaType(
                    mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    schema: new OA\Schema(type: 'string', format: 'binary'),
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 403, description: 'Forbidden'),
            new OA\Response(response: 404, description: 'Generation history not found'),
        ]
    )]
    public function download(Request $request, int $id): Response
    {
        abort_unless($request->user()?->can(DomainPermissions::PRODUCT_GENERATION) ?? false, 403);

        $history = $this->queryBus->dispatch(new DownloadGenerationExcelQuery(generationId: $id));

        return response($history->excelBlob, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => sprintf('attachment; filename="generation-%d.xlsx"', $id),
            'Cache-Control' => 'no-store',
        ]);
    }
}
