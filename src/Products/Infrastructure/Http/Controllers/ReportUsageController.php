<?php

declare(strict_types=1);

namespace Src\Products\Infrastructure\Http\Controllers;

use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Src\Products\Application\Commands\ReportUsage\ReportUsageCommand;
use Src\Products\Infrastructure\Http\Requests\ReportUsageRequest;
use Src\Shared\Core\Bus\CommandBus;
use Src\Shared\Infrastructure\Http\ApiResponseFactory;

final class ReportUsageController
{
    public function __construct(
        private readonly CommandBus $commandBus,
    ) {}

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
        ],
    )]
    public function __invoke(ReportUsageRequest $request, int $id): JsonResponse
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
