<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Src\Identity\Domain\Permissions\DomainPermissions;
use Src\Products\Application\Queries\DownloadGenerationExcel\DownloadGenerationExcelQuery;
use Src\Shared\Core\Bus\QueryBus;

final class DownloadGenerationExcelController
{
    public function __construct(
        private readonly QueryBus $queryBus,
    ) {}

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
        ],
    )]
    public function __invoke(Request $request, int $id): Response
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
