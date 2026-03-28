<?php

declare(strict_types=1);

namespace App\Http\Controllers\Products;

use App\Http\Contracts\Controller;
use App\Http\Requests\Products\ReportUsageRequest;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\ReportUsage\ReportUsageCommand;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

/**
 * Thin HTTP adapter for the Products module — Product Usage resource.
 *
 * Each method follows the single responsibility:
 *   1. Receive (and validate via FormRequest)
 *   2. Build and dispatch the Command through the CommandBus
 *   3. Return a structured JSON response via ApiResponseFactory
 *
 * No business logic lives here.
 */
final class ProductUsageController extends Controller
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

    /**
     * POST /api/v2/products/{id}/usage
     *
     * Reports a usage event for a product.
     * Returns 201 on success or 404 when the product does not exist.
     */
    #[OA\Post(
        path: '/products/{id}/usage',
        summary: 'Report product usage',
        description: 'Records a usage event for the specified product. Requires a valid user ID, product name, and scan timestamp.',
        operationId: 'reportProductUsage',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                description: 'Product ID',
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['userId', 'productName', 'scannedAt'],
                properties: [
                    new OA\Property(property: 'userId', type: 'integer', description: 'ID of the user triggering the usage event', example: 7),
                    new OA\Property(property: 'productName', type: 'string', maxLength: 255, description: 'Name of the product at time of scan', example: 'GPT-4 Pro'),
                    new OA\Property(property: 'scannedAt', type: 'string', format: 'date-time', description: 'ISO-8601 datetime of the scan event', example: '2024-06-15T10:30:00+00:00'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usage event recorded',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/JsonEnvelope',
                ),
            ),
            new OA\Response(response: 401, description: 'Unauthenticated'),
            new OA\Response(response: 404, description: 'Product not found'),
            new OA\Response(response: 422, description: 'Validation error — missing or malformed field; scannedAt must be a strict ISO-8601 datetime with timezone offset'),
        ]
    )]
    public function store(ReportUsageRequest $request, int $id): JsonResponse
    {
        $this->commandBus->dispatch(new ReportUsageCommand(
            productId: $id,
            userId: (int) $request->validated('userId'),
            productName: (string) $request->validated('productName'),
            scannedAt: (string) $request->validated('scannedAt'),
        ));

        return ApiResponseFactory::created(null);
    }
}
