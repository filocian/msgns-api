<?php

declare(strict_types=1);

namespace App\Http\Controllers\Products;

use App\Http\Contracts\Controller;
use App\Http\Requests\Products\GenerateProductsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\GenerateProducts\GenerateProductsCommand;
use Src\Products\Domain\DataTransfer\GenerateProductsInputItem;
use Src\Products\Domain\Ports\ExcelExportPort;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Thin HTTP adapter for the batch product generation endpoint (v2).
 *
 * Content negotiation via Accept header:
 *   - application/vnd.openxmlformats-officedocument.spreadsheetml.sheet → Excel (default)
 *   - application/json → legacy-compatible JSON
 */
#[OA\Post(
    path: '/products/generate',
    summary: 'Generate products in batch',
    description: 'Bulk-generates products from type/quantity pairs. Returns an Excel file by default (one sheet per model). Pass Accept: application/json for a legacy-format JSON response instead.',
    operationId: 'generateProducts',
    tags: ['Products'],
    security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['items'],
            properties: [
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(
                        required: ['typeId', 'quantity'],
                        properties: [
                            new OA\Property(property: 'typeId', type: 'integer', example: 1, description: 'ID of the ProductType to generate'),
                            new OA\Property(property: 'quantity', type: 'integer', example: 10, description: 'Number of units to generate (>= 1)'),
                            new OA\Property(property: 'size', type: 'string', nullable: true, example: 'M', description: 'Optional product size'),
                            new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Special batch', description: 'Optional description override'),
                        ]
                    )
                ),
            ]
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Generated products as Excel file (default) or JSON',
            content: [
                new OA\MediaType(
                    mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    schema: new OA\Schema(type: 'string', format: 'binary')
                ),
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'new_products_count', type: 'integer'),
                            new OA\Property(
                                property: 'product_list',
                                type: 'object',
                                additionalProperties: new OA\AdditionalProperties(
                                    type: 'array',
                                    items: new OA\Items(type: 'string')
                                )
                            ),
                        ]
                    )
                ),
            ]
        ),
        new OA\Response(response: 400, description: 'Validation error'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden — missing product_generation permission'),
        new OA\Response(response: 422, description: 'Invalid typeId or business-rule violation'),
    ]
)]
final class GenerateProductsController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ExcelExportPort $excelExporter,
    ) {}

    public function generate(GenerateProductsRequest $request): JsonResponse|BinaryFileResponse
    {
        $items = array_map(
            static fn (array $item): GenerateProductsInputItem => new GenerateProductsInputItem(
                typeId: (int) $item['typeId'],
                quantity: (int) $item['quantity'],
                size: isset($item['size']) ? (string) $item['size'] : null,
                description: isset($item['description']) ? (string) $item['description'] : null,
            ),
            (array) $request->validated('items'),
        );

        $result = $this->commandBus->dispatch(new GenerateProductsCommand(
            items: array_values($items),
            frontUrl: (string) config('services.products.front_url'),
            passwordLength: (int) config('services.products.default_password_length', 12),
        ));

        // Content negotiation: JSON if explicitly requested, Excel otherwise
        /** @var string $accept */
        $accept = (string) $request->header('Accept', '');

        if (str_contains($accept, 'application/json')) {
            return ApiResponseFactory::ok($result->toJsonArray());
        }

        // Default: stream the Excel file
        $filePath = $this->excelExporter->generate($result);

        return response()->download(
            $filePath,
            'products.xlsx',
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="products.xlsx"',
            ]
        )->deleteFileAfterSend(true);
    }
}
